<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ListFiltersTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-26 12:00:00');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN, 'name' => 'Osama Admin']));

        $this->product = Product::create(['name' => 'Jesco ATF Dextron DX-III 1L', 'sku' => 'JSC-ATF-1002', 'selling_price' => 1000, 'cost_price' => 700, 'stock_quantity' => 500, 'reorder_level' => 20]);
    }

    private function order(string $customer, string $date, float $paid): Order
    {
        $this->post(route('orders.store'), [
            'customer_id' => Customer::firstOrCreate(['name' => $customer])->id,
            'order_date' => $date,
            'status' => 'pending',
            'amount_paid' => $paid,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 1000]],
        ]);

        return Order::latest('id')->first();
    }

    public function test_orders_filter_by_payment_customer_and_date(): void
    {
        $paid = $this->order('Bilal Khan', '2026-09-25', 1000);
        $partial = $this->order('Hassan Raza', '2026-09-02', 400);
        $unpaid = $this->order('Hassan Raza', '2026-08-15', 0);

        $see = fn (array $query) => $this->get(route('orders.index', $query))->assertOk();

        $see(['payment' => 'paid'])->assertSee($paid->order_number)->assertDontSee($partial->order_number)->assertDontSee($unpaid->order_number);
        $see(['payment' => 'unpaid'])->assertSee($unpaid->order_number)->assertDontSee($paid->order_number);
        $see(['customer' => $partial->customer_id])->assertSee($partial->order_number)->assertDontSee($paid->order_number);
        $see(['period' => 'this_month'])->assertSee($paid->order_number)->assertSee($partial->order_number)->assertDontSee($unpaid->order_number);
        $see(['period' => 'last_month'])->assertSee($unpaid->order_number)->assertDontSee($paid->order_number);
        $see(['period' => 'today'])->assertDontSee($paid->order_number);
        $see(['period' => 'custom', 'from' => '2026-09-01', 'to' => '2026-09-10'])->assertSee($partial->order_number)->assertDontSee($paid->order_number)->assertDontSee($unpaid->order_number);
        $see(['period' => 'custom', 'from' => 'not-a-date'])->assertSee($paid->order_number);
    }

    public function test_purchases_filter_by_payment_and_supplier(): void
    {
        $alRahim = Supplier::create(['name' => 'Al-Rahim Base Oil Traders']);
        $crescent = Supplier::create(['name' => 'Crescent Lube Industries']);

        foreach ([[$alRahim, 'received', 70000], [$crescent, 'received', 0], [$crescent, 'ordered', 0]] as [$supplier, $status, $paid]) {
            $this->post(route('purchases.store'), [
                'supplier_id' => $supplier->id, 'purchase_date' => '2026-09-20', 'status' => $status, 'amount_paid' => $paid,
                'items' => [['product_id' => $this->product->id, 'quantity' => 100, 'unit_cost' => 700]],
            ]);
        }

        $this->get(route('purchases.index')); // use up the last "saved" flash message

        $this->get(route('purchases.index', ['payment' => 'paid']))->assertSee('PO-5501')->assertDontSee('PO-5502')->assertDontSee('PO-5503');
        // Ordered stock owes nothing yet, so it isn't listed as unpaid.
        $this->get(route('purchases.index', ['payment' => 'unpaid']))->assertSee('PO-5502')->assertDontSee('PO-5503');
        $this->get(route('purchases.index', ['supplier' => $crescent->id]))->assertSee('PO-5503')->assertDontSee('PO-5501');
    }

    public function test_products_filter_by_stock_level_and_state(): void
    {
        Product::create(['name' => 'Low Gear Oil', 'sku' => 'LOW-1', 'stock_quantity' => 5, 'reorder_level' => 20]);
        Product::create(['name' => 'Empty Motor Oil', 'sku' => 'OUT-1', 'stock_quantity' => 0, 'reorder_level' => 20, 'is_active' => false]);

        $this->get(route('products.index', ['stock' => 'low']))->assertSee('Low Gear Oil')->assertDontSee('Empty Motor Oil')->assertDontSee('Jesco ATF');
        $this->get(route('products.index', ['stock' => 'out']))->assertSee('Empty Motor Oil')->assertDontSee('Low Gear Oil');
        $this->get(route('products.index', ['stock' => 'in']))->assertSee('Jesco ATF')->assertDontSee('Low Gear Oil');
        $this->get(route('products.index', ['state' => 'inactive']))->assertSee('Empty Motor Oil')->assertDontSee('Low Gear Oil');
    }

    public function test_customers_and_suppliers_filter_by_balance(): void
    {
        $this->order('Bilal Khan', '2026-09-25', 1000);
        $this->order('Hassan Raza', '2026-09-25', 0);

        $this->get(route('customers.index', ['balance' => 'due']))->assertSee('Hassan Raza')->assertDontSee('Bilal Khan');
        $this->get(route('customers.index', ['balance' => 'clear']))->assertSee('Bilal Khan')->assertDontSee('Hassan Raza');

        $owed = Supplier::create(['name' => 'Pak Petro Blenders']);
        Supplier::create(['name' => 'Sind Oil Distributors']);
        $this->post(route('purchases.store'), [
            'supplier_id' => $owed->id, 'purchase_date' => '2026-09-20', 'status' => 'received',
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_cost' => 700]],
        ]);

        $this->get(route('suppliers.index', ['balance' => 'due']))->assertSee('Pak Petro Blenders')->assertDontSee('Sind Oil Distributors');
        $this->get(route('suppliers.index', ['balance' => 'clear']))->assertSee('Sind Oil Distributors')->assertDontSee('Pak Petro Blenders');
    }

    public function test_inventory_movement_filters(): void
    {
        $sold = $this->order('Bilal Khan', '2026-09-25', 0); // creates an "out" movement from an order
        StockMovement::create(['product_id' => $this->product->id, 'type' => 'in', 'quantity' => 77, 'note' => 'Opening stock count']);

        $this->get(route('inventory.index', ['source' => 'manual']))->assertSee('Opening stock count')->assertDontSee($sold->order_number);
        $this->get(route('inventory.index', ['type' => 'out']))->assertSee($sold->order_number)->assertDontSee('Opening stock count');
        $this->get(route('inventory.index', ['period' => 'last_month']))->assertSee('No stock movements match these filters.');
        $this->get(route('inventory.index', ['stock' => 'out']))->assertSee('No products match these filters.');
    }

    public function test_users_filter_by_role(): void
    {
        User::factory()->create(['role' => User::ROLE_WAREHOUSE, 'name' => 'Zahid Warehouse']);
        User::factory()->create(['role' => User::ROLE_SALES, 'name' => 'Kamran Sales']);

        $this->get(route('users.index', ['role' => User::ROLE_WAREHOUSE]))->assertSee('Zahid Warehouse')->assertDontSee('Kamran Sales');
        $this->get(route('users.index', ['search' => 'kamran']))->assertSee('Kamran Sales')->assertDontSee('Zahid Warehouse');
    }
}
