<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Support\PartyPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $allSuppliers = Supplier::all();
        $summary = [
            'count' => $allSuppliers->count(),
            'purchased' => abs((float) Transaction::where('type', 'purchase')->whereNotNull('supplier_id')->sum('amount')),
            'payable' => $allSuppliers->sum(fn (Supplier $s) => max(0, $s->balance())),
        ];

        $suppliers = Supplier::query()
            ->withCount('purchases')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('phone', 'like', '%'.$request->string('search').'%')
            ))
            ->when($request->input('balance') === 'due', fn ($q) => $q->whereHas('transactions', fn ($q) => $q->where('type', 'purchase')->where('status', '!=', 'paid')))
            ->when($request->input('balance') === 'clear', fn ($q) => $q->whereDoesntHave('transactions', fn ($q) => $q->where('type', 'purchase')->where('status', '!=', 'paid')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $suppliers->getCollection()->transform(function (Supplier $supplier) {
            $supplier->total_purchased = abs((float) $supplier->transactions()->where('type', 'purchase')->sum('amount'));
            $supplier->balance_amount = $supplier->balance();

            return $supplier;
        });

        return view('suppliers.index', compact('suppliers', 'summary'));
    }

    public function create(): View
    {
        return view('suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::create($this->validateSupplier($request));

        return redirect()->route('suppliers.show', $supplier)->with('status', 'Supplier added successfully.');
    }

    public function show(Supplier $supplier): View
    {
        $purchases = $supplier->purchases()->with(['items', 'transactions'])->latest('purchase_date')->latest('id')->get();
        $openInvoices = $purchases->filter(fn ($p) => $p->balanceDue() > 0)->sortBy('purchase_date')
            ->map(fn ($p) => ['id' => $p->id, 'label' => $p->purchase_number.' ('.$p->purchase_date->format('d M').')', 'due' => $p->balanceDue()])
            ->values();
        $transactions = $supplier->transactions()->latest('transaction_date')->latest('id')->get();
        $balance = $supplier->balance();
        $totalPurchased = abs((float) $supplier->transactions()->where('type', 'purchase')->sum('amount'));

        return view('suppliers.show', compact('supplier', 'purchases', 'transactions', 'balance', 'totalPurchased', 'openInvoices'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validateSupplier($request));

        return redirect()->route('suppliers.show', $supplier)->with('status', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchases()->exists() || $supplier->transactions()->exists()) {
            return back()->with('error', 'This supplier has purchase or payment history and cannot be deleted.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('status', 'Supplier deleted.');
    }

    public function storePayment(Request $request, Supplier $supplier): RedirectResponse
    {
        $openPurchases = $supplier->purchases()->with(['items', 'transactions'])
            ->orderBy('purchase_date')->orderBy('id')->get()
            ->filter(fn (Purchase $purchase) => $purchase->balanceDue() > 0)
            ->values();

        $amount = PartyPayment::apply($request, $openPurchases);

        return redirect()->route('suppliers.show', $supplier)->with('status', 'Payment of Rs. '.number_format($amount).' recorded.');
    }

    private function validateSupplier(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
        ]);
    }
}
