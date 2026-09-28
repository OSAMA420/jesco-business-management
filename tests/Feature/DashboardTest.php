<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function dashboardAs(string $role)
    {
        $this->actingAs(User::factory()->create(['role' => $role, 'name' => 'Osama Ahmed']));

        return $this->get(route('dashboard'))->assertOk();
    }

    public function test_admin_sees_everything(): void
    {
        $this->dashboardAs(User::ROLE_ADMIN)
            ->assertSee('Good')->assertSee('Osama')
            ->assertSee('Sales This Month')->assertSee('Cash Collected')->assertSee('Net Profit This Month')
            ->assertSee('Recent Orders')->assertSee('Needs Attention')->assertSee('New Purchase')
            ->assertSee('Stock Movement')->assertSee('Stock Levels');
    }

    public function test_sales_staff_see_sales_but_not_money_or_stock_areas(): void
    {
        $this->dashboardAs(User::ROLE_SALES)
            ->assertSee('Sales This Month')->assertSee('Customers Owe You')->assertSee('Recent Orders')->assertSee('Add Customer')
            ->assertDontSee('Cash Collected')->assertDontSee('Net Profit This Month')->assertDontSee('New Purchase')
            ->assertDontSee('Add Expense')->assertDontSee('Stock Movement');
    }

    public function test_warehouse_staff_get_a_full_stock_dashboard(): void
    {
        $this->dashboardAs(User::ROLE_WAREHOUSE)
            ->assertSee('Stock Value (at cost)')->assertSee('Units In This Month')->assertSee('Stock Movement')
            ->assertSee('Stock Levels')->assertSee('Recent Stock Movements')->assertSee('Stock In / Out')
            ->assertDontSee('Sales This Month')->assertDontSee('Recent Orders')->assertDontSee('Customers Owe You');
    }

    public function test_figures_come_from_real_data(): void
    {
        Carbon::setTestNow('2026-09-29 10:00:00');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $product = Product::create(['name' => 'Jesco ATF Dextron DX-III 1L', 'sku' => 'JSC-ATF-1002', 'cost_price' => 700, 'selling_price' => 1000, 'stock_quantity' => 25, 'reorder_level' => 20]);
        $customer = Customer::create(['name' => 'Hassan Raza']);
        $order = fn (string $date, int $qty, float $paid) => $this->post(route('orders.store'), [
            'customer_id' => $customer->id, 'order_date' => $date, 'status' => 'pending', 'amount_paid' => $paid,
            'items' => [['product_id' => $product->id, 'quantity' => $qty, 'unit_price' => 1000]],
        ]);
        $order('2026-08-20', 2, 2000);   // last month, same days range (1st to 29th)
        $order('2026-09-28', 4, 1500);   // this month; stock now 19, below reorder level 20

        $page = $this->get(route('dashboard'))->assertOk();
        $kpis = $page->viewData('kpis');

        $this->assertEquals(4000, $kpis['sales']);
        $this->assertEquals(100.0, $kpis['sales_change']);
        $this->assertEquals(1500, $kpis['collected']);
        $this->assertEquals(4000 - 2800, $kpis['profit']);
        $this->assertEquals(2500, $kpis['receivable']);
        $this->assertEquals(1, $kpis['low_stock']);
        $this->assertEquals(6, $kpis['units_out']);

        $this->assertEquals([0, 0, 0, 0, 0, 4000, 0], $page->viewData('salesTrend')['7d']['series']['sales']);
        $this->assertSame(['Wed', 'Thu', 'Fri', 'Sat', 'Sun', 'Mon', 'Tue'], $page->viewData('salesTrend')['7d']['labels']);
        $this->assertEquals(4000, collect($page->viewData('salesTrend')['12m']['series']['sales'])->last());

        $attention = collect($page->viewData('attention'))->keyBy(fn ($a) => $a['label'][1]);
        $this->assertSame(1, $attention['products low on stock']['count']);
        $this->assertSame(2, $attention['orders waiting to be delivered']['count']);
        $this->assertSame(0, $attention['customers owing for over 30 days']['count']);
    }
}
