<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Support\DateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public const STATUSES = ['pending', 'processing', 'delivered', 'cancelled'];

    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with(['customer', 'items', 'transactions'])
            ->withCount('items')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('order_number', 'like', '%'.$request->string('search').'%')
                ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ))
            ->when($request->filled('customer'), fn ($q) => $q->where('customer_id', $request->integer('customer')))
            ->when(in_array($request->input('payment'), ['paid', 'partial', 'unpaid'], true), fn ($q) => $q
                ->whereHas('transactions', fn ($q) => $q->where('type', Order::BILL_TYPE)->where('status', $request->input('payment'))))
            ->tap(fn ($q) => DateFilter::apply($q, $request, 'order_date'))
            ->latest('order_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $customers = Customer::orderBy('name')->pluck('name', 'id');

        return view('orders.index', compact('orders', 'customers'));
    }

    public function create(): View
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('orders.create', compact('customers', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'order_date' => ['required', 'date'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $order = DB::transaction(function () use ($data) {
                $order = Order::create([
                    'order_number' => $this->nextOrderNumber(),
                    'customer_id' => $data['customer_id'],
                    'order_date' => $data['order_date'],
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?? null,
                ]);

                $total = 0;

                foreach ($data['items'] as $line) {
                    $product = Product::lockForUpdate()->findOrFail($line['product_id']);

                    if ($data['status'] !== 'cancelled' && $line['quantity'] > $product->stock_quantity) {
                        throw ValidationException::withMessages([
                            'items' => "Not enough stock for \"{$product->name}\": only {$product->stock_quantity} available.",
                        ]);
                    }

                    $item = OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'unit_cost' => $product->cost_price,
                    ]);

                    $total += $item->quantity * $item->unit_price;

                    if ($data['status'] !== 'cancelled') {
                        $product->decrement('stock_quantity', $item->quantity);

                        StockMovement::create([
                            'product_id' => $product->id,
                            'type' => 'out',
                            'quantity' => $item->quantity,
                            'reference_type' => Order::class,
                            'reference_id' => $order->id,
                            'note' => 'Sold via '.$order->order_number,
                        ]);
                    }
                }

                if ($data['status'] !== 'cancelled') {
                    Transaction::create([
                        'type' => 'sale',
                        'reference_type' => Order::class,
                        'reference_id' => $order->id,
                        'customer_id' => $order->customer_id,
                        'description' => 'Sale '.$order->order_number,
                        'amount' => $total,
                        'status' => 'unpaid',
                        'transaction_date' => $order->order_date,
                    ]);

                    $paidNow = round((float) ($data['amount_paid'] ?? 0), 2);
                    if ($paidNow > $total) {
                        throw ValidationException::withMessages([
                            'amount_paid' => 'Amount paid cannot be more than the order total of Rs. '.number_format($total).'.',
                        ]);
                    }
                    if ($paidNow > 0) {
                        $order->recordPayment($paidNow, $data['order_date'], 'Paid at the time of order');
                    }
                }

                return $order;
            });
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('orders.show', $order)->with('status', 'Order created successfully.');
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'items.product', 'transactions']);

        return view('orders.show', compact('order'));
    }

    public function invoice(Order $order): View
    {
        $order->load(['customer', 'items.product', 'transactions']);

        return view('orders.invoice', [
            'order' => $order,
            'invoiceNumber' => 'INV-'.str_replace('ORD-', '', $order->order_number),
            'company' => config('jesco'),
        ]);
    }

    public function edit(Order $order): View
    {
        $order->load('customer');

        return view('orders.edit', compact('order'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'order_date' => ['required', 'date'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string'],
        ]);

        $wasCancelled = $order->status === 'cancelled';
        $isNowCancelled = $data['status'] === 'cancelled';

        if (! $wasCancelled && $isNowCancelled && $order->amountPaid() > 0) {
            return back()->withInput()->withErrors([
                'status' => 'This order has Rs. '.number_format($order->amountPaid()).' of payments recorded. Remove those payments first, then cancel it.',
            ]);
        }

        DB::transaction(function () use ($order, $data, $wasCancelled, $isNowCancelled) {
            if (! $wasCancelled && $isNowCancelled) {
                $this->restoreStockForOrder($order);
                $order->transactions()->delete();
            } elseif ($wasCancelled && ! $isNowCancelled) {
                $this->deductStockForOrder($order);
                Transaction::create([
                    'type' => 'sale',
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'description' => 'Sale '.$order->order_number,
                    'amount' => $order->total(),
                    'status' => 'unpaid',
                    'transaction_date' => $data['order_date'],
                ]);
            }

            $order->update($data);
        });

        return redirect()->route('orders.show', $order)->with('status', 'Order updated successfully.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        if ($order->amountPaid() > 0) {
            return back()->with('error', 'This order has Rs. '.number_format($order->amountPaid()).' of payments recorded. Remove those payments first, then delete it.');
        }

        DB::transaction(function () use ($order) {
            if ($order->status !== 'cancelled') {
                $this->restoreStockForOrder($order);
            }

            $order->stockMovements()->delete();
            $order->transactions()->delete();
            $order->delete();
        });

        return redirect()->route('orders.index')->with('status', 'Order deleted.');
    }

    public function storePayment(Request $request, Order $order): RedirectResponse
    {
        $order->load('items', 'transactions');
        $due = $order->balanceDue();

        $data = $request->validate([
            'payment_amount' => ['required', 'numeric', 'min:1', 'max:'.$due],
            'payment_date' => ['required', 'date'],
            'payment_note' => ['nullable', 'string', 'max:255'],
        ], [
            'payment_amount.max' => $due > 0 ? 'Amount cannot be more than the Rs. '.number_format($due).' due.' : 'Nothing is due on this order.',
        ]);

        $order->recordPayment((float) $data['payment_amount'], $data['payment_date'], $data['payment_note'] ?? null);

        return redirect()->route('orders.show', $order)->with('status', 'Payment of Rs. '.number_format($data['payment_amount']).' recorded.');
    }

    public function destroyPayment(Order $order, Transaction $payment): RedirectResponse
    {
        abort_unless($payment->type === Order::PAYMENT_TYPE && $payment->reference_type === Order::class && $payment->reference_id === $order->id, 404);

        $payment->delete();
        $order->syncPaymentStatus();

        return redirect()->route('orders.show', $order)->with('status', 'Payment removed.');
    }

    private function restoreStockForOrder(Order $order): void
    {
        foreach ($order->items as $item) {
            $item->product?->increment('stock_quantity', $item->quantity);

            StockMovement::create([
                'product_id' => $item->product_id,
                'type' => 'in',
                'quantity' => $item->quantity,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'note' => 'Restocked from cancelled '.$order->order_number,
            ]);
        }
    }

    private function deductStockForOrder(Order $order): void
    {
        foreach ($order->items as $item) {
            $item->product?->decrement('stock_quantity', $item->quantity);

            StockMovement::create([
                'product_id' => $item->product_id,
                'type' => 'out',
                'quantity' => $item->quantity,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'note' => 'Sold via '.$order->order_number,
            ]);
        }
    }

    private function nextOrderNumber(): string
    {
        $max = Order::query()->pluck('order_number')
            ->map(fn ($number) => (int) str_replace('ORD-', '', $number))
            ->max() ?? 1000;

        return 'ORD-'.($max + 1);
    }
}
