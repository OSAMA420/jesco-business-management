<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PartyDeleteTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-21 12:00:00');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $this->product = Product::create(['name' => 'Jesco ATF Dextron DX-III 1L', 'cost_price' => 700, 'selling_price' => 1000, 'stock_quantity' => 50]);
    }

    private function sellTo(Customer $customer, int $qty, int $paid): void
    {
        $this->post(route('orders.store'), [
            'customer_id' => $customer->id, 'order_date' => '2026-11-20', 'status' => 'delivered',
            'amount_paid' => $paid, 'paid_method' => 'cash',
            'items' => [['product_id' => $this->product->id, 'quantity' => $qty, 'unit_price' => 1000]],
        ])->assertSessionHasNoErrors();
    }

    private function buyFrom(Supplier $supplier, int $qty): Purchase
    {
        $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id, 'purchase_date' => '2026-11-20', 'status' => 'received', 'payment_status' => 'unpaid',
            'items' => [['product_id' => $this->product->id, 'quantity' => $qty, 'unit_cost' => 700]],
        ])->assertSessionHasNoErrors();

        return Purchase::latest('id')->first();
    }

    public function test_deleting_a_customer_removes_their_sales_from_finance_and_reports(): void
    {
        $hassan = Customer::create(['name' => 'Hassan Raza']);
        $other = Customer::create(['name' => 'Al-Madina Autos']);
        $this->sellTo($hassan, 5, 3000);
        $this->sellTo($hassan, 2, 0);
        $this->sellTo($other, 1, 1000);
        $this->assertSame(42, $this->product->fresh()->stock_quantity);

        $this->delete(route('customers.destroy', $hassan))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('status', 'Customer deleted, along with 2 orders and their payments.');

        $this->assertNull($hassan->fresh());
        $this->assertSame(1, Order::count());
        $this->assertSame(1, OrderItem::count());
        $this->assertSame(0, Transaction::where('customer_id', $hassan->id)->count());
        $this->assertSame(1, StockMovement::count());
        $this->assertSame(42, $this->product->fresh()->stock_quantity);   // stock stays as it is

        $profit = $this->get(route('finance.index', ['period' => 'this_month']))->assertOk()->viewData('profit');
        $this->assertEquals(1000, $profit['revenue']);
        $this->assertEquals(1000, $this->get(route('finance.index'))->viewData('accounts')['list']['cash']['balance']);
        $this->get(route('reports.show', 'sales'))->assertOk()->assertDontSee('Hassan Raza');
    }

    public function test_deleting_a_supplier_removes_their_purchases_and_payments(): void
    {
        $supplier = Supplier::create(['name' => 'Al-Rahim Base Oil Traders']);
        $purchase = $this->buyFrom($supplier, 100);
        $this->post(route('purchases.payments.store', $purchase), ['payment_amount' => 20000, 'payment_date' => '2026-11-20', 'payment_method' => 'cash'])->assertSessionHasNoErrors();
        $this->assertSame(150, $this->product->fresh()->stock_quantity);

        $this->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('status', 'Supplier deleted, along with 1 purchase and their payments.');

        $this->assertNull($supplier->fresh());
        $this->assertSame(0, Purchase::count());
        $this->assertSame(0, PurchaseItem::count());
        $this->assertSame(0, Transaction::count());
        $this->assertSame(0, StockMovement::count());
        $this->assertSame(150, $this->product->fresh()->stock_quantity);   // stock stays as it is
        $this->assertEquals(0, $this->get(route('finance.index'))->viewData('accounts')['list']['cash']['balance']);
    }

    public function test_delete_button_warns_when_there_is_history(): void
    {
        $customer = Customer::create(['name' => 'Hassan Raza']);
        $this->sellTo($customer, 1, 0);
        $this->buyFrom(Supplier::create(['name' => 'Al-Rahim Base Oil Traders']), 10);

        $this->get(route('customers.index'))->assertOk()->assertSee('1 order and all payments will also be deleted', false);
        $this->get(route('suppliers.index'))->assertOk()->assertSee('1 purchase and all payments will also be deleted', false);
    }
}
