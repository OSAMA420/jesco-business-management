<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Support\DateFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $stock = Product::query()
            ->withSum(['stockMovements as stock_in' => fn ($q) => $q->where('type', 'in')], 'quantity')
            ->withSum(['stockMovements as stock_out' => fn ($q) => $q->where('type', 'out')], 'quantity')
            ->when($request->filled('stock_search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$request->string('stock_search').'%')
                ->orWhere('sku', 'like', '%'.$request->string('stock_search').'%')
            ))
            ->when($request->filled('stock'), fn ($q) => match ($request->input('stock')) {
                'out' => $q->where('stock_quantity', '<=', 0),
                'low' => $q->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'reorder_level'),
                'in' => $q->whereColumn('stock_quantity', '>', 'reorder_level'),
                default => $q,
            })
            ->orderBy('name')
            ->get();

        $movements = StockMovement::with(['product', 'reference'])
            ->when(in_array($request->input('type'), ['in', 'out'], true), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('product'), fn ($q) => $q->where('product_id', $request->integer('product')))
            ->when($request->filled('source'), fn ($q) => match ($request->input('source')) {
                'order' => $q->where('reference_type', Order::class),
                'purchase' => $q->where('reference_type', Purchase::class),
                'manual' => $q->whereNull('reference_type'),
                default => $q,
            })
            ->tap(fn ($q) => DateFilter::apply($q, $request, 'created_at'))
            ->latest('id')
            ->paginate(20, pageName: 'movements_page')
            ->withQueryString();

        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('inventory.index', compact('stock', 'movements', 'products'));
    }

    public function storeMovement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['required', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($data['product_id']);

        if ($data['type'] === 'out' && $data['quantity'] > $product->stock_quantity) {
            return back()
                ->withInput()
                ->with('error', "Not enough stock: only {$product->stock_quantity} units of \"{$product->name}\" available.");
        }

        StockMovement::create([
            'product_id' => $product->id,
            'type' => $data['type'],
            'quantity' => $data['quantity'],
            'note' => $data['note'] ?? null,
        ]);

        $product->increment('stock_quantity', $data['type'] === 'in' ? $data['quantity'] : -$data['quantity']);

        $label = $data['type'] === 'in' ? 'Stock in' : 'Stock out';

        return redirect()->route('inventory.index')->with('status', "{$label} recorded for {$product->name}.");
    }
}
