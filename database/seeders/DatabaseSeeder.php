<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@jesco.pk'],
            ['name' => 'Admin', 'password' => bcrypt('password')]
        );

        $categories = collect([
            'Motorcycle Engine Oil',
            'Motor Engine Oil',
            'Transmission Fluid',
            'Gear Oil',
        ])->mapWithKeys(fn (string $name) => [
            $name => Category::updateOrCreate(
                ['slug' => str($name)->slug()],
                ['name' => $name]
            ),
        ]);

        $products = collect([
            ['name' => 'Jesco Motorcycle Engine Oil API SF/CD 20W50 4T', 'sku' => 'JSC-MC-0007', 'category' => 'Motorcycle Engine Oil', 'cost' => 650, 'price' => 850, 'stock' => 420, 'reorder' => 100],
            ['name' => 'Jesco Motor Engine Oil API SF/CF 20W60 1L', 'sku' => 'JSC-ME-1001', 'category' => 'Motor Engine Oil', 'cost' => 880, 'price' => 1150, 'stock' => 15, 'reorder' => 30],
            ['name' => 'Jesco ATF Dextron DX-III Transmission Fluid 1L', 'sku' => 'JSC-ATF-1002', 'category' => 'Transmission Fluid', 'cost' => 1020, 'price' => 1350, 'stock' => 0, 'reorder' => 25],
            ['name' => 'Jesco MP Gold Gear Oil GL-4 90W140 1L', 'sku' => 'JSC-GO-1003', 'category' => 'Gear Oil', 'cost' => 740, 'price' => 980, 'stock' => 210, 'reorder' => 50],
            ['name' => 'Jesco Motor Engine Oil API CF-4/SJ 20W50 3L', 'sku' => 'JSC-ME-3004', 'category' => 'Motor Engine Oil', 'cost' => 2250, 'price' => 2950, 'stock' => 65, 'reorder' => 40],
            ['name' => 'Jesco Motor Engine Oil API SF/CF 20W60 3L', 'sku' => 'JSC-ME-3005', 'category' => 'Motor Engine Oil', 'cost' => 2320, 'price' => 3050, 'stock' => 8, 'reorder' => 20],
        ])->map(fn (array $p) => Product::updateOrCreate(
            ['sku' => $p['sku']],
            [
                'category_id' => $categories[$p['category']]->id,
                'name' => $p['name'],
                'cost_price' => $p['cost'],
                'selling_price' => $p['price'],
                'stock_quantity' => $p['stock'],
                'reorder_level' => $p['reorder'],
                'is_active' => $p['sku'] !== 'JSC-ATF-1002',
            ]
        ));

        $productBySku = $products->keyBy('sku');

        $customers = collect([
            ['name' => 'Bilal Khan', 'company' => 'Al-Madina Auto Workshop', 'phone' => '0320-8853243'],
            ['name' => 'Usman Ahmed', 'company' => 'Quetta Terminal Motors', 'phone' => '0322-9988776'],
            ['name' => 'Hassan Raza', 'company' => 'Raza Automotive Garage', 'phone' => '0345-1122334'],
            ['name' => 'Kamran Sheikh', 'company' => 'Baldia Spare Parts & Lubricants', 'phone' => '0333-5566778'],
        ])->mapWithKeys(fn (array $c) => [
            $c['name'] => Customer::updateOrCreate(['name' => $c['name']], $c),
        ]);

        $supplier = Supplier::updateOrCreate(
            ['name' => 'Al-Rahim Base Oil Traders'],
            ['phone' => '0300-1234567', 'address' => 'Site Area, Karachi']
        );

        // Purchase: stock coming in from the supplier
        $purchase = Purchase::updateOrCreate(
            ['purchase_number' => 'PO-5521'],
            ['supplier_id' => $supplier->id, 'purchase_date' => now()->subDays(3), 'status' => 'received', 'payment_status' => 'paid']
        );

        if ($purchase->items()->doesntExist()) {
            $item = PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id' => $productBySku['JSC-MC-0007']->id,
                'quantity' => 200,
                'unit_cost' => 650,
            ]);

            StockMovement::create([
                'product_id' => $item->product_id,
                'type' => 'in',
                'quantity' => $item->quantity,
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'note' => 'Received from '.$supplier->name,
            ]);

            Transaction::create([
                'type' => 'purchase',
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'supplier_id' => $supplier->id,
                'description' => 'Purchase '.$purchase->purchase_number,
                'amount' => -($item->quantity * $item->unit_cost),
                'status' => 'unpaid',
                'transaction_date' => $purchase->purchase_date,
            ]);

            $purchase->recordPayment($item->quantity * $item->unit_cost, $purchase->purchase_date->toDateString(), 'Bank transfer');
        }

        // Orders: sales to customers
        $orders = [
            [
                'number' => 'ORD-1042', 'customer' => 'Bilal Khan', 'date' => now()->subDays(1), 'status' => 'processing', 'paid' => 'full',
                'items' => [['sku' => 'JSC-ME-1001', 'qty' => 10], ['sku' => 'JSC-GO-1003', 'qty' => 12]],
            ],
            [
                'number' => 'ORD-1041', 'customer' => 'Kamran Sheikh', 'date' => now()->subDays(1), 'status' => 'delivered', 'paid' => 'full',
                'items' => [['sku' => 'JSC-MC-0007', 'qty' => 6]],
            ],
            [
                'number' => 'ORD-1040', 'customer' => 'Hassan Raza', 'date' => now()->subDays(2), 'status' => 'pending', 'paid' => 40000,
                'items' => [['sku' => 'JSC-ME-3004', 'qty' => 20], ['sku' => 'JSC-ME-3005', 'qty' => 12]],
            ],
            [
                'number' => 'ORD-1039', 'customer' => 'Usman Ahmed', 'date' => now()->subDays(3), 'status' => 'delivered', 'paid' => 'full',
                'items' => [['sku' => 'JSC-ATF-1002', 'qty' => 8], ['sku' => 'JSC-GO-1003', 'qty' => 5]],
            ],
            [
                'number' => 'ORD-1038', 'customer' => 'Usman Ahmed', 'date' => now()->subDays(3), 'status' => 'cancelled', 'paid' => 0,
                'items' => [['sku' => 'JSC-ATF-1002', 'qty' => 30]],
            ],
        ];

        foreach ($orders as $data) {
            $order = Order::updateOrCreate(
                ['order_number' => $data['number']],
                [
                    'customer_id' => $customers[$data['customer']]->id,
                    'order_date' => $data['date'],
                    'status' => $data['status'],
                ]
            );

            if ($order->items()->exists()) {
                continue;
            }

            $total = 0;

            foreach ($data['items'] as $line) {
                $product = $productBySku[$line['sku']];

                $item = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $line['qty'],
                    'unit_price' => $product->selling_price,
                ]);

                $total += $item->quantity * $item->unit_price;

                if ($data['status'] !== 'cancelled') {
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

                $paid = $data['paid'] === 'full' ? $total : $data['paid'];
                if ($paid > 0) {
                    $order->recordPayment($paid, $order->order_date->toDateString(), 'Cash');
                }
            }
        }

        // A standalone business expense
        Transaction::updateOrCreate(
            ['type' => 'expense', 'description' => 'Electricity Bill'],
            ['amount' => -14200, 'status' => 'paid', 'transaction_date' => now()->subDays(2)]
        );
    }
}
