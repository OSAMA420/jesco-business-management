<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $monthStart = now()->startOfMonth();
        $products = Product::orderBy('name')->get();

        $ordersThisMonth = $this->salesOrders($monthStart, now());
        $sales = $this->billed($ordersThisMonth);
        // Compare with the same number of days last month, so early in the month isn't unfairly low.
        $lastMonthSoFar = $this->billed($this->salesOrders($monthStart->copy()->subMonthNoOverflow(), now()->subMonthNoOverflow()));

        $items = $ordersThisMonth->flatMap->items;
        $expenses = abs((float) Transaction::where('type', 'expense')->whereDate('transaction_date', '>=', $monthStart->toDateString())->whereDate('transaction_date', '<=', now()->toDateString())->sum('amount'));
        $receivables = Customer::all()->map->balance()->filter(fn ($b) => $b > 0);

        $kpis = [
            'sales' => $sales,
            'sales_change' => $lastMonthSoFar > 0 ? round(($sales - $lastMonthSoFar) / $lastMonthSoFar * 100, 1) : null,
            'collected' => (float) Transaction::where('type', 'payment_in')->whereDate('transaction_date', '>=', $monthStart->toDateString())->whereDate('transaction_date', '<=', now()->toDateString())->sum('amount'),
            'profit' => $sales - (float) $items->sum(fn (OrderItem $i) => $i->quantity * $i->unit_cost) - $expenses,
            'receivable' => (float) $receivables->sum(),
            'receivable_customers' => $receivables->count(),
            'stock_value' => (float) $products->sum(fn (Product $p) => max(0, $p->stock_quantity) * $p->cost_price),
            'low_stock' => $products->filter->isLowStock()->count(),
            'out_of_stock' => $products->filter->isOutOfStock()->count(),
            'units_in' => (int) StockMovement::where('type', 'in')->where('created_at', '>=', $monthStart)->sum('quantity'),
            'units_out' => (int) StockMovement::where('type', 'out')->where('created_at', '>=', $monthStart)->sum('quantity'),
        ];

        return view('dashboard', [
            'kpis' => $kpis,
            'salesTrend' => $this->trend(fn (Carbon $from, Carbon $to) => ['sales' => $this->billed($this->salesOrders($from, $to))]),
            'stockTrend' => $this->trend(fn (Carbon $from, Carbon $to) => [
                'in' => (int) StockMovement::where('type', 'in')->whereBetween('created_at', [$from, $to])->sum('quantity'),
                'out' => (int) StockMovement::where('type', 'out')->whereBetween('created_at', [$from, $to])->sum('quantity'),
            ]),
            'attention' => $this->attention($products),
            'statuses' => collect(['pending', 'processing', 'delivered', 'cancelled'])
                ->mapWithKeys(fn ($s) => [$s => Order::where('status', $s)->count()])->all(),
            'recentOrders' => Order::with(['customer', 'items', 'transactions'])->latest('order_date')->latest('id')->take(6)->get(),
            'topProducts' => $items->groupBy('product_id')
                ->map(fn (Collection $rows) => [
                    $rows->first()->product?->name ?? 'Deleted product',
                    (int) $rows->sum('quantity'),
                    (float) $rows->sum(fn (OrderItem $i) => $i->quantity * $i->unit_price),
                ])
                ->sortByDesc(2)->take(5)->values()->all(),
            // Closest to (or below) their reorder level first.
            'stockLevels' => $products->where('is_active', true)
                ->sortBy(fn (Product $p) => $p->reorder_level > 0 ? $p->stock_quantity / $p->reorder_level : PHP_INT_MAX)
                ->take(6)->values(),
            'recentMovements' => StockMovement::with(['product', 'reference'])->latest('id')->take(8)->get(),
        ]);
    }

    private function salesOrders(Carbon $from, Carbon $to): Collection
    {
        return Order::with('items.product')
            ->where('status', '!=', 'cancelled')
            ->whereDate('order_date', '>=', $from->toDateString())->whereDate('order_date', '<=', $to->toDateString())
            ->get();
    }

    private function billed(Collection $orders): float
    {
        return (float) $orders->sum(fn (Order $o) => $o->total());
    }

    /**
     * Runs $measure over each day of the last week, each of the last five weeks, and each of the last twelve months.
     *
     * @param  callable(Carbon, Carbon): array<string, float|int>  $measure
     */
    private function trend(callable $measure): array
    {
        $buckets = [
            '7d' => collect(range(6, 0))->map(fn ($i) => [now()->subDays($i)->startOfDay(), now()->subDays($i)->endOfDay(), now()->subDays($i)->format('D')]),
            '30d' => collect(range(4, 0))->map(fn ($i) => [now()->subWeeks($i)->startOfWeek(), now()->subWeeks($i)->endOfWeek(), now()->subWeeks($i)->startOfWeek()->format('d M')]),
            '12m' => collect(range(11, 0))->map(fn ($i) => [now()->startOfMonth()->subMonthsNoOverflow($i), now()->startOfMonth()->subMonthsNoOverflow($i)->endOfMonth(), now()->startOfMonth()->subMonthsNoOverflow($i)->format('M')]),
        ];

        return collect($buckets)->map(function (Collection $ranges) use ($measure) {
            $values = $ranges->map(fn ($r) => $measure($r[0], $r[1]));

            return [
                'labels' => $ranges->pluck(2)->all(),
                'series' => collect(array_keys($values->first()))->mapWithKeys(fn ($key) => [$key => $values->pluck($key)->all()])->all(),
            ];
        })->all();
    }

    private function attention(Collection $products): array
    {
        $low = $products->filter(fn (Product $p) => $p->isLowStock() || $p->isOutOfStock())->sortBy('stock_quantity');
        $overdueCustomers = Order::with(['items', 'transactions'])
            ->where('status', '!=', 'cancelled')
            ->where('order_date', '<', now()->subDays(30)->toDateString())
            ->get()
            ->filter(fn (Order $o) => $o->balanceDue() > 0)
            ->pluck('customer_id')->unique()->count();

        return [
            [
                'area' => 'inventory', 'count' => $low->count(), 'tone' => 'amber',
                'label' => ['product low on stock', 'products low on stock'],
                'detail' => $low->take(2)->map(fn (Product $p) => $p->name.', '.$p->stock_quantity.' left')->implode(' · '),
                'url' => route('inventory.index', ['stock' => $low->every->isOutOfStock() ? 'out' : 'low']),
            ],
            [
                'area' => 'orders', 'count' => Order::whereIn('status', ['pending', 'processing'])->count(), 'tone' => 'sky',
                'label' => ['order waiting to be delivered', 'orders waiting to be delivered'],
                'detail' => 'Pending or processing',
                'url' => route('orders.index', ['status' => Order::where('status', 'pending')->exists() ? 'pending' : 'processing']),
            ],
            [
                'area' => 'customers', 'count' => $overdueCustomers, 'tone' => 'rose',
                'label' => ['customer owing for over 30 days', 'customers owing for over 30 days'],
                'detail' => auth()->user()->canAccess('reports') ? 'See who in the Dues report' : 'See customers with dues',
                'url' => auth()->user()->canAccess('reports') ? route('reports.show', 'dues') : route('customers.index', ['balance' => 'due']),
            ],
            [
                'area' => 'purchases', 'count' => Purchase::where('status', 'ordered')->count(), 'tone' => 'gray',
                'label' => ['purchase not received yet', 'purchases not received yet'],
                'detail' => 'Stock ordered from suppliers',
                'url' => route('purchases.index', ['status' => 'ordered']),
            ],
        ];
    }
}
