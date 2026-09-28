<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Support\DateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public const STATUSES = ['ordered', 'received', 'cancelled'];

    public function index(Request $request): View
    {
        $purchases = Purchase::query()
            ->with(['supplier', 'items', 'transactions'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('purchase_number', 'like', '%'.$request->string('search').'%')
                ->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ))
            ->when($request->filled('supplier'), fn ($q) => $q->where('supplier_id', $request->integer('supplier')))
            ->when(in_array($request->input('payment'), ['paid', 'partial', 'unpaid'], true), fn ($q) => $q
                ->where('status', 'received')->where('payment_status', $request->input('payment')))
            ->tap(fn ($q) => DateFilter::apply($q, $request, 'purchase_date'))
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->pluck('name', 'id');

        return view('purchases.index', compact('purchases', 'suppliers'));
    }

    public function create(): View
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('purchases.create', compact('suppliers', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $purchase = DB::transaction(function () use ($data) {
                $purchase = Purchase::create([
                    'purchase_number' => $this->nextPurchaseNumber(),
                    'supplier_id' => $data['supplier_id'],
                    'purchase_date' => $data['purchase_date'],
                    'status' => $data['status'],
                    'payment_status' => 'unpaid',
                    'notes' => $data['notes'] ?? null,
                ]);

                foreach ($data['items'] as $line) {
                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'unit_cost' => $line['unit_cost'],
                    ]);
                }

                if ($purchase->status === 'received') {
                    $this->receive($purchase);

                    $paidNow = round((float) ($data['amount_paid'] ?? 0), 2);
                    if ($paidNow > $purchase->total()) {
                        throw ValidationException::withMessages([
                            'amount_paid' => 'Amount paid cannot be more than the purchase total of Rs. '.number_format($purchase->total()).'.',
                        ]);
                    }
                    if ($paidNow > 0) {
                        $purchase->recordPayment($paidNow, $data['purchase_date'], 'Paid at the time of purchase');
                    }
                }

                return $purchase;
            });
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('purchases.show', $purchase)->with('status', 'Purchase '.$purchase->purchase_number.' saved.');
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load(['supplier', 'items.product', 'transactions']);

        return view('purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase): View
    {
        $purchase->load('supplier');

        return view('purchases.edit', compact('purchase'));
    }

    public function update(Request $request, Purchase $purchase): RedirectResponse
    {
        $data = $request->validate([
            'purchase_date' => ['required', 'date'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string'],
        ]);

        $wasReceived = $purchase->status === 'received';
        $isNowReceived = $data['status'] === 'received';

        try {
            DB::transaction(function () use ($purchase, $data, $wasReceived, $isNowReceived) {
                $purchase->update($data);

                if ($wasReceived && ! $isNowReceived) {
                    $this->unreceive($purchase);
                } elseif (! $wasReceived && $isNowReceived) {
                    $this->receive($purchase);
                }
            });
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('purchases.show', $purchase)->with('status', 'Purchase updated successfully.');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        try {
            DB::transaction(function () use ($purchase) {
                if ($purchase->status === 'received') {
                    $this->unreceive($purchase);
                }

                $purchase->stockMovements()->delete();
                $purchase->transactions()->delete();
                $purchase->delete();
            });
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('purchases.index')->with('status', 'Purchase deleted.');
    }

    public function storePayment(Request $request, Purchase $purchase): RedirectResponse
    {
        $purchase->load('items', 'transactions');
        $due = $purchase->balanceDue();

        $data = $request->validate([
            'payment_amount' => ['required', 'numeric', 'min:1', 'max:'.$due],
            'payment_date' => ['required', 'date'],
            'payment_note' => ['nullable', 'string', 'max:255'],
        ], [
            'payment_amount.max' => $due > 0 ? 'Amount cannot be more than the Rs. '.number_format($due).' due.' : 'Nothing is due on this purchase.',
        ]);

        $purchase->recordPayment((float) $data['payment_amount'], $data['payment_date'], $data['payment_note'] ?? null);

        return redirect()->route('purchases.show', $purchase)->with('status', 'Payment of Rs. '.number_format($data['payment_amount']).' recorded.');
    }

    public function destroyPayment(Purchase $purchase, Transaction $payment): RedirectResponse
    {
        abort_unless($payment->type === Purchase::PAYMENT_TYPE && $payment->reference_type === Purchase::class && $payment->reference_id === $purchase->id, 404);

        $payment->delete();
        $purchase->syncPaymentStatus();

        return redirect()->route('purchases.show', $purchase)->with('status', 'Payment removed.');
    }

    /**
     * Stock arrives: add it to inventory and record what is owed to the supplier.
     */
    private function receive(Purchase $purchase): void
    {
        $purchase->load('items');

        foreach ($purchase->items as $item) {
            Product::whereKey($item->product_id)->increment('stock_quantity', $item->quantity);

            StockMovement::create([
                'product_id' => $item->product_id,
                'type' => 'in',
                'quantity' => $item->quantity,
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'note' => 'Received via '.$purchase->purchase_number,
            ]);
        }

        Transaction::create([
            'type' => 'purchase',
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
            'supplier_id' => $purchase->supplier_id,
            'description' => 'Purchase '.$purchase->purchase_number,
            'amount' => -$purchase->total(),
            'status' => 'unpaid',
            'transaction_date' => $purchase->purchase_date,
        ]);
    }

    /**
     * Undo a receipt: take the stock back out and drop the amount owed.
     * Refuses if some of that stock has already been sold.
     */
    private function unreceive(Purchase $purchase): void
    {
        $purchase->load('items.product', 'transactions');

        if ($purchase->amountPaid() > 0) {
            throw ValidationException::withMessages([
                'status' => 'This purchase has Rs. '.number_format($purchase->amountPaid()).' of payments recorded. Remove those payments first.',
            ]);
        }

        foreach ($purchase->items as $item) {
            $product = Product::lockForUpdate()->find($item->product_id);

            if ($product && $product->stock_quantity < $item->quantity) {
                throw ValidationException::withMessages([
                    'status' => "Can't take this stock back out: only {$product->stock_quantity} of \"{$product->name}\" left in inventory, but this purchase added {$item->quantity}.",
                ]);
            }

            $product?->decrement('stock_quantity', $item->quantity);

            StockMovement::create([
                'product_id' => $item->product_id,
                'type' => 'out',
                'quantity' => $item->quantity,
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'note' => 'Reversed from '.$purchase->purchase_number,
            ]);
        }

        $purchase->transactions()->delete();
    }

    private function nextPurchaseNumber(): string
    {
        $max = Purchase::query()->pluck('purchase_number')
            ->map(fn ($number) => (int) str_replace('PO-', '', $number))
            ->max() ?? 5500;

        return 'PO-'.($max + 1);
    }
}
