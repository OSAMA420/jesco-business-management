<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Support\DateFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public const REPORTS = [
        'sales' => ['title' => 'Sales Report', 'description' => 'How much was sold and collected, by month, product and customer.', 'icon' => 'sales'],
        'profit-loss' => ['title' => 'Profit & Loss', 'description' => 'Sales minus cost of goods and expenses, with profit per product.', 'icon' => 'finance'],
        'stock' => ['title' => 'Stock Report', 'description' => 'Stock value, low and out-of-stock items, and what is selling.', 'icon' => 'inventory'],
        'purchases' => ['title' => 'Purchase Report', 'description' => 'What was bought, from which supplier, and what is still owed.', 'icon' => 'purchase'],
        'dues' => ['title' => 'Dues (Aging)', 'description' => 'Who owes money and for how long, customers and suppliers.', 'icon' => 'ledger'],
        'customer-statement' => ['title' => 'Customer Statement', 'description' => "One customer's orders and payments with a running balance, ready to send them.", 'icon' => 'customer'],
        'supplier-statement' => ['title' => 'Supplier Statement', 'description' => "One supplier's purchases and the payments made to them, with a running balance.", 'icon' => 'supplier'],
    ];

    public function index(): View
    {
        return view('reports.index', ['reports' => self::REPORTS]);
    }

    public function show(Request $request, string $report): View
    {
        return view('reports.'.$report, [
            'report' => $report,
            'meta' => self::REPORTS[$report],
        ] + $this->data($request, $report));
    }

    public function csv(Request $request, string $report): StreamedResponse
    {
        $tables = $this->data($request, $report)['tables'];
        $filename = 'jesco-'.$report.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($tables) {
            $out = fopen('php://output', 'w');
            if (empty($tables)) {
                fputcsv($out, ['Nothing to export. Choose a customer or supplier first.']);
            }
            foreach ($tables as $title => $table) {
                fputcsv($out, [$title]);
                fputcsv($out, $table['columns']);
                foreach ($table['rows'] as $row) {
                    fputcsv($out, $row);
                }
                fputcsv($out, []);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function data(Request $request, string $report): array
    {
        return $this->{Str::camel($report).'Data'}($request);
    }

    /** Orders that count as sales (not cancelled) in the chosen period. */
    private function salesOrders(Request $request): Collection
    {
        return Order::query()
            ->with(['items.product', 'customer', 'transactions'])
            ->where('status', '!=', 'cancelled')
            ->tap(fn ($q) => DateFilter::apply($q, $request, 'order_date'))
            ->get();
    }

    /** Sold lines grouped by product: qty, revenue, cost. */
    private function soldByProduct(Collection $orders): Collection
    {
        return $orders->flatMap->items
            ->groupBy('product_id')
            ->map(fn (Collection $items) => [
                'name' => $items->first()->product?->name ?? 'Deleted product',
                'qty' => (int) $items->sum('quantity'),
                'revenue' => (float) $items->sum(fn (OrderItem $i) => $i->quantity * $i->unit_price),
                'cost' => (float) $items->sum(fn (OrderItem $i) => $i->quantity * $i->unit_cost),
            ])
            ->sortByDesc('revenue')
            ->values();
    }

    private static function percent(float $part, float $whole): string
    {
        return $whole > 0 ? round($part / $whole * 100).'%' : '—';
    }

    private function salesData(Request $request): array
    {
        $orders = $this->salesOrders($request);
        $billed = (float) $orders->sum(fn (Order $o) => $o->total());
        $received = (float) DateFilter::apply(Transaction::where('type', 'payment_in'), $request, 'transaction_date')->sum('amount');
        $products = $this->soldByProduct($orders);

        $monthly = collect(range(5, 0))->map(function (int $ago) {
            $start = now()->startOfMonth()->subMonthsNoOverflow($ago);

            return [
                'label' => $start->format('M'),
                'value' => (float) Order::with('items')->where('status', '!=', 'cancelled')
                    ->whereDate('order_date', '>=', $start->toDateString())->whereDate('order_date', '<=', $start->copy()->endOfMonth()->toDateString())
                    ->get()->sum(fn (Order $o) => $o->total()),
            ];
        });

        $customers = $orders->groupBy('customer_id')->map(fn (Collection $group) => [
            $group->first()->customer?->name ?? 'Deleted customer',
            $group->count(),
            $group->sum(fn (Order $o) => $o->total()),
            $group->sum(fn (Order $o) => $o->amountPaid()),
            $group->sum(fn (Order $o) => $o->balanceDue()),
        ])->sortByDesc(2)->values()->all();

        return [
            'kpis' => [
                'orders' => $orders->count(),
                'billed' => $billed,
                'received' => $received,
                'average' => $orders->count() ? round($billed / $orders->count()) : 0,
            ],
            'monthly' => $monthly,
            'tables' => [
                'Sales by product' => [
                    'columns' => ['Product', 'Qty Sold', 'Revenue (Rs.)', 'Share'],
                    'rows' => $products->map(fn ($p) => [$p['name'], $p['qty'], $p['revenue'], self::percent($p['revenue'], $billed)])->all(),
                ],
                'Sales by customer' => [
                    'columns' => ['Customer', 'Orders', 'Billed (Rs.)', 'Received (Rs.)', 'Due (Rs.)'],
                    'rows' => $customers,
                ],
            ],
        ];
    }

    private function profitLossData(Request $request): array
    {
        $products = $this->soldByProduct($this->salesOrders($request));
        $revenue = (float) $products->sum('revenue');
        $cogs = (float) $products->sum('cost');

        $expenses = DateFilter::apply(Transaction::where('type', 'expense'), $request, 'transaction_date')
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get()
            ->map(fn ($row) => [FinanceController::EXPENSE_CATEGORIES[$row->category] ?? 'Other', abs((float) $row->total)])
            ->sortByDesc(1)
            ->values()
            ->all();
        $expenseTotal = (float) array_sum(array_column($expenses, 1));

        return [
            'statement' => [
                'revenue' => $revenue,
                'cogs' => $cogs,
                'gross' => $revenue - $cogs,
                'expenses' => $expenses,
                'expenseTotal' => $expenseTotal,
                'net' => $revenue - $cogs - $expenseTotal,
            ],
            'tables' => [
                'Profit by product' => [
                    'columns' => ['Product', 'Qty Sold', 'Revenue (Rs.)', 'Cost (Rs.)', 'Profit (Rs.)', 'Margin'],
                    'rows' => $products
                        ->map(fn ($p) => [$p['name'], $p['qty'], $p['revenue'], $p['cost'], $p['revenue'] - $p['cost'], self::percent($p['revenue'] - $p['cost'], $p['revenue'])])
                        ->sortByDesc(4)->values()->all(),
                ],
                'Profit & loss statement' => [
                    'columns' => ['Line', 'Amount (Rs.)'],
                    'rows' => array_merge(
                        [['Sales revenue', $revenue], ['Cost of goods sold', -$cogs], ['Gross profit', $revenue - $cogs]],
                        array_map(fn ($e) => ['Expense: '.$e[0], -$e[1]], $expenses),
                        [['Net profit', $revenue - $cogs - $expenseTotal]],
                    ),
                    'csvOnly' => true,
                ],
            ],
        ];
    }

    private function stockData(Request $request): array
    {
        $soldQty = $this->salesOrders($request)->flatMap->items->groupBy('product_id')->map->sum('quantity');
        $products = Product::orderBy('name')->get();

        return [
            'kpis' => [
                'cost_value' => (float) $products->sum(fn (Product $p) => max(0, $p->stock_quantity) * $p->cost_price),
                'sale_value' => (float) $products->sum(fn (Product $p) => max(0, $p->stock_quantity) * $p->selling_price),
                'low' => $products->filter->isLowStock()->count(),
                'out' => $products->filter->isOutOfStock()->count(),
            ],
            'tables' => [
                'Stock by product' => [
                    'columns' => ['Product', 'SKU', 'In Stock', 'Reorder Level', 'Sold (period)', 'Stock Value at Cost (Rs.)', 'Status'],
                    'rows' => $products->map(fn (Product $p) => [
                        $p->name,
                        $p->sku,
                        $p->stock_quantity,
                        $p->reorder_level,
                        (int) ($soldQty[$p->id] ?? 0),
                        max(0, $p->stock_quantity) * (float) $p->cost_price,
                        $p->isOutOfStock() ? 'Out of Stock' : ($p->isLowStock() ? 'Low Stock' : 'In Stock'),
                    ])->all(),
                ],
            ],
        ];
    }

    private function purchasesData(Request $request): array
    {
        $purchases = Purchase::query()
            ->with(['items.product', 'supplier', 'transactions'])
            ->where('status', 'received')
            ->tap(fn ($q) => DateFilter::apply($q, $request, 'purchase_date'))
            ->get();

        $bySupplier = $purchases->groupBy('supplier_id')->map(fn (Collection $group) => [
            $group->first()->supplier?->name ?? 'Deleted supplier',
            $group->count(),
            $group->sum(fn (Purchase $p) => $p->total()),
            $group->sum(fn (Purchase $p) => $p->amountPaid()),
            $group->sum(fn (Purchase $p) => $p->balanceDue()),
        ])->sortByDesc(2)->values()->all();

        $byProduct = $purchases->flatMap->items->groupBy('product_id')->map(function (Collection $items) {
            $qty = (int) $items->sum('quantity');
            $total = (float) $items->sum(fn (PurchaseItem $i) => $i->quantity * $i->unit_cost);

            return [$items->first()->product?->name ?? 'Deleted product', $qty, $qty ? round($total / $qty, 2) : 0, $total];
        })->sortByDesc(3)->values()->all();

        return [
            'kpis' => [
                'count' => $purchases->count(),
                'total' => (float) $purchases->sum(fn (Purchase $p) => $p->total()),
                'paid' => (float) $purchases->sum(fn (Purchase $p) => $p->amountPaid()),
                'due' => (float) $purchases->sum(fn (Purchase $p) => $p->balanceDue()),
            ],
            'tables' => [
                'Purchases by supplier' => [
                    'columns' => ['Supplier', 'Purchases', 'Total (Rs.)', 'Paid (Rs.)', 'Due (Rs.)'],
                    'rows' => $bySupplier,
                ],
                'Purchases by product' => [
                    'columns' => ['Product', 'Qty Bought', 'Avg Unit Cost (Rs.)', 'Total (Rs.)'],
                    'rows' => $byProduct,
                ],
            ],
        ];
    }

    private function duesData(Request $request): array
    {
        $columns = fn (string $party) => [$party, '0-30 days (Rs.)', '31-60 days (Rs.)', '61-90 days (Rs.)', 'Over 90 days (Rs.)', 'Total (Rs.)'];

        $orders = Order::with(['items', 'transactions', 'customer'])->where('status', '!=', 'cancelled')->get();
        $purchases = Purchase::with(['items', 'transactions', 'supplier'])->where('status', 'received')->get();

        return [
            'tables' => [
                'Customers (receivable)' => [
                    'columns' => $columns('Customer'),
                    'rows' => $this->aging($orders, 'order_date', fn (Order $o) => $o->customer?->name ?? 'Deleted customer'),
                ],
                'Suppliers (payable)' => [
                    'columns' => $columns('Supplier'),
                    'rows' => $this->aging($purchases, 'purchase_date', fn (Purchase $p) => $p->supplier?->name ?? 'Deleted supplier'),
                ],
            ],
        ];
    }

    private function customerStatementData(Request $request): array
    {
        return $this->statement($request, [
            'kind' => 'customer',
            'model' => Customer::class,
            'bill_type' => 'sale',
            'payment_type' => 'payment_in',
            'bill_class' => Order::class,
            'number' => 'order_number',
            'date' => 'order_date',
            'bills' => 'orders',
            'billed_text' => 'Order %s billed',
            'paid_text' => 'Payment received',
            'paid_column' => 'Paid',
            'bills_title' => 'Orders',
            'bill_column' => 'Order',
        ]);
    }

    private function supplierStatementData(Request $request): array
    {
        return $this->statement($request, [
            'kind' => 'supplier',
            'model' => Supplier::class,
            'bill_type' => 'purchase',
            'payment_type' => 'payment_out',
            'bill_class' => Purchase::class,
            'number' => 'purchase_number',
            'date' => 'purchase_date',
            'bills' => 'purchases',
            'billed_text' => 'Purchase %s received',
            'paid_text' => 'Payment made',
            'paid_column' => 'Paid',
            'bills_title' => 'Purchases',
            'bill_column' => 'Purchase',
        ]);
    }

    /**
     * One customer's or supplier's bills and payments for the period, like a
     * bank statement: opening balance, every entry with a running balance,
     * closing balance, plus the bills dated in the period.
     */
    private function statement(Request $request, array $cfg): array
    {
        $parties = $cfg['model']::orderBy('name')->get();
        $party = $request->filled($cfg['kind']) ? $cfg['model']::find($request->integer($cfg['kind'])) : null;

        if (! $party) {
            return ['cfg' => $cfg, 'parties' => $parties, 'party' => null, 'tables' => []];
        }

        [$from, $to] = DateFilter::range($request) ?? [null, null];
        $entries = $party->transactions()
            ->with('reference')
            ->whereIn('type', [$cfg['bill_type'], $cfg['payment_type']])
            ->orderBy('transaction_date')->orderBy('id')
            ->get();

        $before = $from ? $entries->filter(fn (Transaction $t) => $t->transaction_date->lt($from->copy()->startOfDay())) : collect();
        $inPeriod = $entries->diff($before)
            ->when($to, fn ($c) => $c->filter(fn (Transaction $t) => $t->transaction_date->lte($to->copy()->endOfDay())))
            ->values();

        // Bills raise the balance and payments lower it; stored signs differ by side, so work in absolute amounts.
        $effect = fn (Transaction $t) => abs((float) $t->amount) * ($t->type === $cfg['bill_type'] ? 1 : -1);
        $opening = (float) $before->sum($effect);
        $balance = $opening;

        $lines = $inPeriod->map(function (Transaction $t) use (&$balance, $cfg) {
            $isBill = $t->type === $cfg['bill_type'];
            $bill = $t->reference instanceof $cfg['bill_class'] ? $t->reference : null;
            $number = $bill?->{$cfg['number']};
            $amount = abs((float) $t->amount);
            $balance += $isBill ? $amount : -$amount;

            return (object) [
                'date' => $t->transaction_date,
                'details' => $isBill ? sprintf($cfg['billed_text'], $number ?? '') : $cfg['paid_text'].($number ? ' for '.$number : ''),
                // Skip notes that only repeat the heading, and the data migration's internal note.
                'note' => $isBill || $t->description === $cfg['paid_text'] || Str::startsWith((string) $t->description, 'Paid in full (recorded before')
                    ? null : $t->description,
                'method' => $t->methodLabel(),
                'reference' => $t->reference_no,
                'billed' => $isBill ? $amount : 0.0,
                'paid' => $isBill ? 0.0 : $amount,
                'balance' => $balance,
                'bill' => $bill,
                'number' => $number,
            ];
        });

        $bills = $party->{$cfg['bills']}()
            ->with(['items.product', 'transactions'])
            ->tap(fn ($q) => DateFilter::apply($q, $request, $cfg['date']))
            ->orderBy($cfg['date'])->orderBy('id')
            ->get();

        $summary = [
            'opening' => $opening,
            'billed' => (float) $lines->sum('billed'),
            'paid' => (float) $lines->sum('paid'),
            'closing' => $balance,
        ];

        return [
            'cfg' => $cfg,
            'parties' => $parties,
            'party' => $party,
            'summary' => $summary,
            'lines' => $lines,
            'bills' => $bills,
            'tables' => [
                'Statement for '.$party->name => [
                    'columns' => ['Date', 'Details', 'Paid Via', 'Reference', 'Billed (Rs.)', 'Paid (Rs.)', 'Balance (Rs.)'],
                    'rows' => array_merge(
                        [['', 'Opening balance', '', '', '', '', $opening]],
                        $lines->map(fn ($l) => [$l->date->format('Y-m-d'), trim($l->details.($l->note ? ' ('.$l->note.')' : '')), $l->method ?? '', $l->reference ?? '', $l->billed ?: '', $l->paid ?: '', $l->balance])->all(),
                        [['', 'Closing balance', '', '', $summary['billed'], $summary['paid'], $balance]],
                    ),
                    'csvOnly' => true,
                ],
                $cfg['bills_title'] => [
                    'columns' => [$cfg['bill_column'], 'Date', 'Items', 'Status', 'Total (Rs.)', 'Paid (Rs.)', 'Due (Rs.)'],
                    'rows' => $bills->map(fn ($b) => [
                        $b->{$cfg['number']},
                        $b->{$cfg['date']}->format('Y-m-d'),
                        $b->items->map(fn ($i) => ($i->product?->name ?? 'Deleted product').' x '.$i->quantity)->implode(', '),
                        ucfirst($b->status),
                        $b->status === 'cancelled' ? 0 : $b->total(),
                        $b->amountPaid(),
                        $b->balanceDue(),
                    ])->all(),
                ],
            ],
        ];
    }

    /**
     * Unpaid amounts per party, split by how many days old each bill is.
     */
    private function aging(Collection $bills, string $dateColumn, callable $partyName): array
    {
        return $bills
            ->filter(fn ($bill) => $bill->balanceDue() > 0)
            ->groupBy(fn ($bill) => $partyName($bill))
            ->map(function (Collection $group, string $name) use ($dateColumn) {
                $buckets = [0, 0, 0, 0];
                foreach ($group as $bill) {
                    $days = (int) abs($bill->{$dateColumn}->diffInDays(now()->startOfDay()));
                    $buckets[match (true) { $days <= 30 => 0, $days <= 60 => 1, $days <= 90 => 2, default => 3 }] += $bill->balanceDue();
                }

                return [$name, ...$buckets, array_sum($buckets)];
            })
            ->sortByDesc(5)
            ->values()
            ->all();
    }
}
