<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Support\DateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public const EXPENSE_CATEGORIES = [
        'rent' => 'Rent',
        'salaries' => 'Salaries',
        'utilities' => 'Electricity & Utilities',
        'transport' => 'Fuel & Transport',
        'maintenance' => 'Maintenance',
        'packaging' => 'Packaging',
        'marketing' => 'Marketing',
        'other' => 'Other',
    ];

    public const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'bank' => 'Bank Transfer',
        'cheque' => 'Cheque',
    ];

    public const TRANSACTION_TYPES = [
        'sale' => 'Sale',
        'purchase' => 'Purchase',
        'payment_in' => 'Payment In',
        'payment_out' => 'Payment Out',
        'expense' => 'Expense',
    ];

    public function index(Request $request): View
    {
        $tab = in_array($request->input('tab'), ['overview', 'transactions', 'expenses'], true) ? $request->input('tab') : 'overview';

        $data = match ($tab) {
            'transactions' => ['transactions' => $this->transactions($request)],
            'expenses' => $this->expenses($request),
            default => $this->overview($request),
        };

        return view('finance.index', ['tab' => $tab] + $data);
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        Transaction::create(['type' => 'expense', 'status' => 'paid'] + $this->validateExpense($request));

        return redirect()->route('finance.index', ['tab' => 'expenses'])->with('status', 'Expense saved.');
    }

    public function updateExpense(Request $request, Transaction $expense): RedirectResponse
    {
        abort_unless($expense->type === 'expense', 404);

        $expense->update($this->validateExpense($request));

        return redirect()->route('finance.index', ['tab' => 'expenses'])->with('status', 'Expense updated.');
    }

    public function destroyExpense(Transaction $expense): RedirectResponse
    {
        abort_unless($expense->type === 'expense', 404);

        $expense->delete();

        return redirect()->route('finance.index', ['tab' => 'expenses'])->with('status', 'Expense deleted.');
    }

    private function overview(Request $request): array
    {
        $inPeriod = fn (string ...$types) => DateFilter::apply(Transaction::whereIn('type', $types), $request, 'transaction_date');

        $summary = [
            'cash_in' => (float) $inPeriod('payment_in')->sum('amount'),
            'cash_out' => abs((float) $inPeriod('payment_out', 'expense')->sum('amount')),
            'expenses' => abs((float) $inPeriod('expense')->sum('amount')),
        ];
        $summary['net'] = $summary['cash_in'] - $summary['cash_out'];

        $receivables = $this->outstanding(Customer::all());
        $payables = $this->outstanding(Supplier::all());
        $summary['receivable'] = $receivables->sum('due');
        $summary['payable'] = $payables->sum('due');

        // Always the last six calendar months, whatever the period filter says.
        $monthly = collect(range(5, 0))->map(function (int $ago) {
            $start = now()->startOfMonth()->subMonthsNoOverflow($ago);
            $between = fn (string ...$types) => Transaction::whereIn('type', $types)
                ->whereDate('transaction_date', '>=', $start->toDateString())->whereDate('transaction_date', '<=', $start->copy()->endOfMonth()->toDateString());

            return [
                'month' => $start->format('M'),
                'in' => (float) $between('payment_in')->sum('amount'),
                'out' => abs((float) $between('payment_out', 'expense')->sum('amount')),
            ];
        });

        $byCategory = $inPeriod('expense')
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get()
            ->map(fn ($row) => ['label' => self::EXPENSE_CATEGORIES[$row->category] ?? 'Other', 'total' => abs((float) $row->total)])
            ->sortByDesc('total')
            ->values();

        return [
            'summary' => $summary,
            'receivables' => $receivables->take(5),
            'payables' => $payables->take(5),
            'monthly' => $monthly,
            'byCategory' => $byCategory,
        ];
    }

    /**
     * Parties with money still due, biggest first.
     */
    private function outstanding(Collection $parties): Collection
    {
        return $parties
            ->map(fn ($party) => ['id' => $party->id, 'name' => $party->name, 'due' => $party->balance()])
            ->filter(fn ($row) => $row['due'] > 0)
            ->sortByDesc('due')
            ->values();
    }

    private function transactions(Request $request)
    {
        $search = '%'.$request->string('search').'%';

        return Transaction::query()
            ->with(['customer', 'supplier', 'reference'])
            ->when(array_key_exists((string) $request->input('type'), self::TRANSACTION_TYPES), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('description', 'like', $search)
                ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', $search))
                ->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', $search))
                ->orWhereHasMorph('reference', [Order::class], fn ($q) => $q->where('order_number', 'like', $search))
                ->orWhereHasMorph('reference', [Purchase::class], fn ($q) => $q->where('purchase_number', 'like', $search))
            ))
            ->tap(fn ($q) => DateFilter::apply($q, $request, 'transaction_date'))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();
    }

    private function expenses(Request $request): array
    {
        $query = Transaction::where('type', 'expense')
            ->when(array_key_exists((string) $request->input('category'), self::EXPENSE_CATEGORIES), fn ($q) => $q->where('category', $request->input('category')))
            ->tap(fn ($q) => DateFilter::apply($q, $request, 'transaction_date'));

        return [
            'expenseTotal' => abs((float) (clone $query)->sum('amount')),
            'expenses' => $query->latest('transaction_date')->latest('id')->paginate(20)->withQueryString(),
        ];
    }

    private function validateExpense(Request $request): array
    {
        $data = $request->validate([
            'expense_date' => ['required', 'date'],
            'category' => ['required', Rule::in(array_keys(self::EXPENSE_CATEGORIES))],
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', Rule::in(array_keys(self::PAYMENT_METHODS))],
            'description' => ['required', 'string', 'max:255'],
        ]);

        return [
            'transaction_date' => $data['expense_date'],
            'category' => $data['category'],
            'amount' => -abs((float) $data['amount']),
            'payment_method' => $data['method'],
            'description' => $data['description'],
        ];
    }
}
