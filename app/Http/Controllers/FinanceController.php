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
use Illuminate\Support\Facades\DB;
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

    public const PAYMENT_METHODS = Transaction::PAYMENT_METHODS;

    public const TRANSACTION_TYPES = [
        'sale' => 'Sale',
        'purchase' => 'Purchase',
        'payment_in' => 'Payment In',
        'payment_out' => 'Payment Out',
        'expense' => 'Expense',
        'opening' => 'Opening Balance',
    ];

    public function index(Request $request): View
    {
        $tab = in_array($request->input('tab'), ['overview', 'cashbook', 'transactions', 'expenses'], true) ? $request->input('tab') : 'overview';

        $data = match ($tab) {
            'cashbook' => $this->cashBook($request),
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

        $periodMoney = $inPeriod('payment_in', 'payment_out', 'expense')->get();
        $byAccount = fn (Collection $rows) => collect(Transaction::ACCOUNTS)
            ->map(fn ($label, $key) => abs((float) $rows->filter(fn ($t) => $t->account() === $key)->sum('amount')))
            ->put('none', abs((float) $rows->filter(fn ($t) => $t->account() === null)->sum('amount')));
        $flows = [
            'in' => $byAccount($periodMoney->where('type', 'payment_in')),
            'suppliers' => $byAccount($periodMoney->where('type', 'payment_out')),
            'expenses' => $byAccount($periodMoney->where('type', 'expense')),
        ];
        $summary['paid_suppliers'] = (float) $flows['suppliers']->sum();

        return [
            'summary' => $summary,
            'accounts' => $this->accounts($request),
            'flows' => $flows,
            'profit' => $this->profit($request, $summary['expenses']),
            'receivables' => $receivables->take(5),
            'payables' => $payables->take(5),
            'monthly' => $monthly,
            'byCategory' => $byCategory,
        ];
    }

    /**
     * Balance in each account today, plus what moved in and out during the period.
     * Entries whose payment method wasn't recorded can't be placed in an account.
     */
    private function accounts(Request $request): array
    {
        $all = Transaction::whereIn('type', ['payment_in', 'payment_out', 'expense', 'opening'])->get();
        [$from, $to] = DateFilter::range($request) ?? [null, null];
        $inRange = fn (Transaction $t) => (! $from || $t->transaction_date->gte($from->copy()->startOfDay()))
            && (! $to || $t->transaction_date->lte($to->copy()->endOfDay()));

        $accounts = collect(Transaction::ACCOUNTS)->map(function ($label, $key) use ($all, $inRange) {
            $rows = $all->filter(fn (Transaction $t) => $t->account() === $key);
            $period = $rows->filter($inRange);

            return [
                'label' => $label,
                'opening' => (float) $rows->where('type', 'opening')->sum(fn ($t) => $t->cashEffect()),
                'balance' => (float) $rows->sum(fn ($t) => $t->cashEffect()),
                'in' => (float) $period->where('type', '!=', 'opening')->sum(fn ($t) => max(0, $t->cashEffect())),
                'out' => abs((float) $period->sum(fn ($t) => min(0, $t->cashEffect()))),
            ];
        });

        $unassigned = $all->filter(fn (Transaction $t) => $t->account() === null && $t->type !== 'opening');

        return [
            'list' => $accounts,
            'openingDate' => $all->firstWhere('type', 'opening')?->transaction_date,
            'total' => (float) $accounts->sum('balance'),
            'unassigned' => (float) $unassigned->sum(fn ($t) => $t->cashEffect()),
            'unassignedCount' => $unassigned->count(),
        ];
    }

    /**
     * Sales billed in the period minus what those goods cost and the period's expenses.
     */
    private function profit(Request $request, float $expenses): array
    {
        $orders = Order::with('items')->where('status', '!=', 'cancelled')
            ->tap(fn ($q) => DateFilter::apply($q, $request, 'order_date'))
            ->get();
        $items = $orders->flatMap->items;
        $revenue = (float) $items->sum(fn ($i) => $i->quantity * $i->unit_price);
        $cogs = (float) $items->sum(fn ($i) => $i->quantity * $i->unit_cost);

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross' => $revenue - $cogs,
            'expenses' => $expenses,
            'net' => $revenue - $cogs - $expenses,
        ];
    }

    /**
     * Day-by-day money book for one account (or all of them): opening balance,
     * every entry with a running balance, and each day's closing.
     */
    private function cashBook(Request $request): array
    {
        $account = array_key_exists((string) $request->input('account'), Transaction::ACCOUNTS) ? $request->input('account') : 'all';
        [$from, $to] = DateFilter::range($request) ?? [null, null];

        $entries = Transaction::with(['customer', 'supplier', 'reference'])
            ->whereIn('type', ['payment_in', 'payment_out', 'expense', 'opening'])
            ->when($account !== 'all', fn ($q) => $q->whereIn('payment_method', Transaction::methodsFor($account)))
            ->orderBy('transaction_date')->orderByRaw("type = 'opening' desc")->orderBy('id')
            ->get();

        $before = $from ? $entries->filter(fn ($t) => $t->transaction_date->lt($from->copy()->startOfDay())) : collect();
        $inPeriod = $entries->diff($before)
            ->when($to, fn ($c) => $c->filter(fn ($t) => $t->transaction_date->lte($to->copy()->endOfDay())))
            ->values();

        $opening = (float) $before->sum(fn ($t) => $t->cashEffect());
        $balance = $opening;
        $days = $inPeriod->groupBy(fn ($t) => $t->transaction_date->toDateString())->map(function ($rows, $date) use (&$balance) {
            $dayOpening = $balance;
            $lines = $rows->map(function (Transaction $t) use (&$balance) {
                $balance += $t->cashEffect();

                return (object) ['t' => $t, 'in' => max(0, $t->cashEffect()), 'out' => abs(min(0, $t->cashEffect())), 'balance' => $balance];
            });

            return (object) [
                'date' => \Illuminate\Support\Carbon::parse($date),
                'opening' => $dayOpening,
                'in' => (float) $lines->sum('in'),
                'out' => (float) $lines->sum('out'),
                'closing' => $balance,
                'lines' => $lines,
            ];
        })->values();

        return [
            'account' => $account,
            'book' => [
                'opening' => $opening,
                'in' => (float) $days->sum('in'),
                'out' => (float) $days->sum('out'),
                'closing' => $balance,
                'days' => $days,
            ],
        ];
    }

    /**
     * One "opening" entry per account. Saving again replaces them, so the figures
     * can be corrected; an empty or zero amount removes that account's opening.
     */
    public function storeOpeningBalances(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'opening_date' => ['required', 'date', 'before_or_equal:today'],
            'opening' => ['nullable', 'array'],
            'opening.*' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
        ], [
            'opening.*.min' => 'Opening balances cannot be negative.',
        ]);

        DB::transaction(function () use ($data) {
            Transaction::where('type', 'opening')->delete();

            foreach (Transaction::ACCOUNTS as $key => $label) {
                $amount = round((float) ($data['opening'][$key] ?? 0), 2);
                if ($amount <= 0) {
                    continue;
                }

                Transaction::create([
                    'type' => 'opening',
                    'payment_method' => $key,
                    'description' => 'Opening balance',
                    'amount' => $amount,
                    'status' => 'paid',
                    'transaction_date' => $data['opening_date'],
                ]);
            }
        });

        return redirect()->route('finance.index')->with('status', 'Opening balances saved.');
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
