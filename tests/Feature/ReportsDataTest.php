<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportsDataTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-29 12:00:00');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->product = Product::create([
            'name' => 'Jesco ATF Dextron DX-III 1L', 'sku' => 'JSC-ATF-1002',
            'cost_price' => 700, 'selling_price' => 1000, 'stock_quantity' => 100, 'reorder_level' => 20,
        ]);
    }

    private function order(string $customer, string $date, int $qty, float $paid = 0, string $status = 'pending'): void
    {
        $this->post(route('orders.store'), [
            'customer_id' => Customer::firstOrCreate(['name' => $customer])->id,
            'order_date' => $date, 'status' => $status, 'amount_paid' => $paid,
            'items' => [['product_id' => $this->product->id, 'quantity' => $qty, 'unit_price' => 1000]],
        ])->assertSessionHasNoErrors();
    }

    private function purchase(string $date, int $qty, float $cost, float $paid = 0): void
    {
        $this->post(route('purchases.store'), [
            'supplier_id' => Supplier::firstOrCreate(['name' => 'Al-Rahim Base Oil Traders'])->id,
            'purchase_date' => $date, 'status' => 'received', 'amount_paid' => $paid,
            'items' => [['product_id' => $this->product->id, 'quantity' => $qty, 'unit_cost' => $cost]],
        ])->assertSessionHasNoErrors();
    }

    public function test_orders_remember_cost_and_received_purchases_update_it(): void
    {
        $this->order('Bilal Khan', '2026-09-10', 10);
        $this->purchase('2026-09-15', 50, 760);
        $this->order('Bilal Khan', '2026-09-20', 5);

        $this->assertEquals(760, $this->product->fresh()->cost_price);
        $this->assertEquals([700, 760], Order::with('items')->orderBy('id')->get()->map(fn ($o) => (float) $o->items->first()->unit_cost)->all());
    }

    public function test_sales_and_profit_loss_figures(): void
    {
        $this->order('Bilal Khan', '2026-09-10', 10, 10000);           // Rs. 10,000, paid
        $this->order('Hassan Raza', '2026-09-20', 5, 2000);            // Rs. 5,000, part paid
        $this->order('Usman Ahmed', '2026-09-21', 3, 0, 'cancelled');  // not a sale
        $this->order('Kamran Sheikh', '2026-08-05', 2);                 // outside "this month"
        $this->post(route('finance.expenses.store'), ['expense_date' => '2026-09-25', 'category' => 'utilities', 'amount' => 1500, 'method' => 'bank', 'description' => 'K-Electric bill']);

        $sales = $this->get(route('reports.show', ['report' => 'sales', 'period' => 'this_month']))->assertOk();
        $this->assertEquals(['orders' => 2, 'billed' => 15000, 'received' => 12000, 'average' => 7500], $sales->viewData('kpis'));
        $this->assertEquals([['Jesco ATF Dextron DX-III 1L', 15, 15000, '100%']], $sales->viewData('tables')['Sales by product']['rows']);
        $this->assertEquals(['Hassan Raza', 1, 5000, 2000, 3000], collect($sales->viewData('tables')['Sales by customer']['rows'])->firstWhere(0, 'Hassan Raza'));
        $this->assertEquals(2000, $sales->viewData('monthly')->firstWhere('label', 'Aug')['value']);

        $pl = $this->get(route('reports.show', ['report' => 'profit-loss', 'period' => 'this_month']))->assertOk();
        $statement = $pl->viewData('statement');
        $this->assertEquals(15000, $statement['revenue']);
        $this->assertEquals(10500, $statement['cogs']);        // 15 x Rs. 700
        $this->assertEquals(4500, $statement['gross']);
        $this->assertEquals([['Electricity & Utilities', 1500]], $statement['expenses']);
        $this->assertEquals(3000, $statement['net']);
        $this->assertEquals(['Jesco ATF Dextron DX-III 1L', 15, 15000, 10500, 4500, '30%'], $pl->viewData('tables')['Profit by product']['rows'][0]);
    }

    public function test_stock_and_purchase_reports(): void
    {
        $this->purchase('2026-09-15', 50, 760, 20000);   // stock 150, cost now 760
        $this->order('Bilal Khan', '2026-09-20', 30);    // stock 120

        $stock = $this->get(route('reports.show', ['report' => 'stock', 'period' => 'this_month']))->assertOk();
        $this->assertEquals(120 * 760, $stock->viewData('kpis')['cost_value']);
        $this->assertEquals(120 * 1000, $stock->viewData('kpis')['sale_value']);
        $row = $stock->viewData('tables')['Stock by product']['rows'][0];
        $this->assertSame([120, 20, 30, 'In Stock'], [$row[2], $row[3], $row[4], $row[6]]);

        $purchases = $this->get(route('reports.show', 'purchases'))->assertOk();
        $this->assertEquals(['count' => 1, 'total' => 38000, 'paid' => 20000, 'due' => 18000], $purchases->viewData('kpis'));
        $this->assertEquals(['Jesco ATF Dextron DX-III 1L', 50, 760, 38000], $purchases->viewData('tables')['Purchases by product']['rows'][0]);
    }

    public function test_dues_are_split_by_bill_age(): void
    {
        $this->order('Hassan Raza', '2026-09-20', 5, 1000);    // 9 days old, Rs. 4,000 due
        $this->order('Hassan Raza', '2026-08-10', 3);          // 50 days old, Rs. 3,000 due
        $this->order('Hassan Raza', '2026-05-01', 1);          // 151 days old, Rs. 1,000 due
        $this->order('Bilal Khan', '2026-09-25', 2, 2000);     // fully paid, not listed
        $this->purchase('2026-07-20', 10, 700);                // 71 days old, Rs. 7,000 due

        $dues = $this->get(route('reports.show', 'dues'))->assertOk()->viewData('tables');

        $this->assertEquals([['Hassan Raza', 4000, 3000, 0, 1000, 8000]], $dues['Customers (receivable)']['rows']);
        $this->assertEquals([['Al-Rahim Base Oil Traders', 0, 0, 7000, 0, 7000]], $dues['Suppliers (payable)']['rows']);
    }

    public function test_csv_contains_the_real_rows(): void
    {
        $this->order('Hassan Raza', '2026-09-20', 5, 2000);

        $csv = $this->get(route('reports.csv', ['report' => 'dues']))->streamedContent();

        $this->assertStringContainsString('"Customers (receivable)"', $csv);
        $this->assertStringContainsString('"Hassan Raza",3000,0,0,0,3000', $csv);
    }
}
