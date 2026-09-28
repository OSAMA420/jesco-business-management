<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Support\PartyPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->withCount('orders')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('company', 'like', '%'.$request->string('search').'%')
                ->orWhere('phone', 'like', '%'.$request->string('search').'%')
            ))
            ->when($request->input('balance') === 'due', fn ($q) => $q->whereHas('transactions', fn ($q) => $q->where('type', 'sale')->where('status', '!=', 'paid')))
            ->when($request->input('balance') === 'clear', fn ($q) => $q->whereDoesntHave('transactions', fn ($q) => $q->where('type', 'sale')->where('status', '!=', 'paid')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $customers->getCollection()->transform(function (Customer $customer) {
            $customer->total_spent = (float) $customer->transactions()->where('type', 'sale')->sum('amount');
            $customer->balance_amount = $customer->balance();

            return $customer;
        });

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCustomer($request);

        $customer = Customer::create($data);

        return redirect()->route('customers.show', $customer)->with('status', 'Customer added successfully.');
    }

    public function show(Customer $customer): View
    {
        $customer->loadCount('orders');

        $orders = $customer->orders()->with(['items', 'transactions'])->latest('order_date')->get();
        $openInvoices = $orders->filter(fn ($o) => $o->balanceDue() > 0)->sortBy('order_date')
            ->map(fn ($o) => ['id' => $o->id, 'label' => $o->order_number.' ('.$o->order_date->format('d M').')', 'due' => $o->balanceDue()])
            ->values();
        $transactions = $customer->transactions()->latest('transaction_date')->get();
        $balance = $customer->balance();
        $totalSpent = (float) $customer->transactions()->where('type', 'sale')->sum('amount');

        return view('customers.show', compact('customer', 'orders', 'transactions', 'balance', 'totalSpent', 'openInvoices'));
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $this->validateCustomer($request);

        $customer->update($data);

        return redirect()->route('customers.show', $customer)->with('status', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->orders()->exists() || $customer->transactions()->exists()) {
            return back()->with('error', 'This customer has order or transaction history and cannot be deleted.');
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Customer deleted.');
    }

    public function storePayment(Request $request, Customer $customer): RedirectResponse
    {
        $openOrders = $customer->orders()->with(['items', 'transactions'])
            ->orderBy('order_date')->orderBy('id')->get()
            ->filter(fn (Order $order) => $order->balanceDue() > 0)
            ->values();

        $amount = PartyPayment::apply($request, $openOrders);

        return redirect()->route('customers.show', $customer)->with('status', 'Payment of Rs. '.number_format($amount).' received.');
    }

    private function validateCustomer(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ]);
    }
}
