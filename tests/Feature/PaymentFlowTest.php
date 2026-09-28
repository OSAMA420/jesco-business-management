<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Supplier $supplier;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->customer = Customer::create(['name' => 'Hassan Raza']);
        $this->supplier = Supplier::create(['name' => 'Al-Rahim Base Oil Traders']);
        $this->product = Product::create([
            'name' => 'Jesco Motor Engine Oil API SF/CF 20W60 1L',
            'sku' => 'JSC-ME-1001',
            'cost_price' => 700,
            'selling_price' => 1000,
            'stock_quantity' => 500,
        ]);
    }

    /** 10 x Rs. 1,000 = Rs. 10,000 */
    private function createOrder(float $amountPaid = 0, string $date = '2026-09-20', string $status = 'pending')
    {
        return $this->post(route('orders.store'), [
            'customer_id' => $this->customer->id,
            'order_date' => $date,
            'status' => $status,
            'amount_paid' => $amountPaid,
            'items' => [['product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 1000]],
        ]);
    }

    /** 100 x Rs. 700 = Rs. 70,000 */
    private function createPurchase(string $status, float $amountPaid = 0)
    {
        return $this->post(route('purchases.store'), [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => '2026-09-20',
            'status' => $status,
            'amount_paid' => $amountPaid,
            'items' => [['product_id' => $this->product->id, 'quantity' => 100, 'unit_cost' => 700]],
        ]);
    }

    private function saleStatus(Order $order): string
    {
        return $order->transactions()->where('type', 'sale')->value('status');
    }

    public function test_order_paid_partly_at_creation(): void
    {
        $this->createOrder(4000)->assertRedirect();
        $order = Order::with('items', 'transactions')->sole();

        $this->assertSame('partial', $this->saleStatus($order));
        $this->assertEquals(4000, $order->amountPaid());
        $this->assertEquals(6000, $order->balanceDue());
        $this->assertEquals(6000, $this->customer->balance());
    }

    public function test_amount_paid_cannot_exceed_order_total(): void
    {
        $this->createOrder(15000)->assertSessionHasErrors('amount_paid');
        $this->assertSame(0, Order::count());
        $this->assertSame(500, $this->product->fresh()->stock_quantity);
    }

    public function test_recording_and_removing_order_payments_updates_status(): void
    {
        $this->createOrder();
        $order = Order::sole();
        $this->assertSame('unpaid', $this->saleStatus($order));

        $this->post(route('orders.payments.store', $order), ['payment_amount' => 12000, 'payment_date' => '2026-09-22'])
            ->assertSessionHasErrors('payment_amount');

        $this->post(route('orders.payments.store', $order), ['payment_amount' => 3000, 'payment_date' => '2026-09-22', 'payment_note' => 'Cash']);
        $this->assertSame('partial', $this->saleStatus($order));

        $this->post(route('orders.payments.store', $order), ['payment_amount' => 7000, 'payment_date' => '2026-09-25']);
        $this->assertSame('paid', $this->saleStatus($order));
        $this->assertEquals(0, $this->customer->balance());

        $payment = Transaction::where('type', 'payment_in')->where('amount', 7000)->sole();
        $this->delete(route('orders.payments.destroy', [$order, $payment]))->assertRedirect(route('orders.show', $order));

        $this->assertSame('partial', $this->saleStatus($order));
        $this->assertEquals(7000, $this->customer->balance());
    }

    public function test_order_with_payments_cannot_be_cancelled_or_deleted(): void
    {
        $this->createOrder(2500);
        $order = Order::sole();

        $this->put(route('orders.update', $order), ['order_date' => '2026-09-20', 'status' => 'cancelled'])
            ->assertSessionHasErrors('status');
        $this->delete(route('orders.destroy', $order))->assertSessionHas('error');
        $this->assertSame('pending', $order->fresh()->status);

        $this->delete(route('orders.payments.destroy', [$order, Transaction::where('type', 'payment_in')->sole()]));
        $this->put(route('orders.update', $order), ['order_date' => '2026-09-20', 'status' => 'cancelled'])
            ->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_customer_payment_applies_to_oldest_orders_first(): void
    {
        $this->createOrder(0, '2026-09-18');
        $this->createOrder(0, '2026-09-10');
        [$older, $newer] = [Order::whereDate('order_date', '2026-09-10')->sole(), Order::whereDate('order_date', '2026-09-18')->sole()];

        $this->post(route('customers.payments.store', $this->customer), ['apply_to' => 'auto', 'payment_amount' => 25000, 'payment_date' => '2026-09-26'])
            ->assertSessionHasErrors('payment_amount');

        $this->post(route('customers.payments.store', $this->customer), ['apply_to' => 'auto', 'payment_amount' => 13000, 'payment_date' => '2026-09-26'])
            ->assertRedirect(route('customers.show', $this->customer));

        $this->assertSame('paid', $this->saleStatus($older));
        $this->assertSame('partial', $this->saleStatus($newer));
        $this->assertEquals(7000, $this->customer->balance());

        // A specific bill, capped at that bill's own balance.
        $this->post(route('customers.payments.store', $this->customer), ['apply_to' => (string) $newer->id, 'payment_amount' => 8000, 'payment_date' => '2026-09-26'])
            ->assertSessionHasErrors('payment_amount');
        $this->post(route('customers.payments.store', $this->customer), ['apply_to' => (string) $older->id, 'payment_amount' => 100, 'payment_date' => '2026-09-26'])
            ->assertSessionHasErrors('apply_to');
    }

    public function test_purchase_payments(): void
    {
        $this->createPurchase('received', 20000);
        $purchase = Purchase::sole();
        $this->assertSame('partial', $purchase->fresh()->payment_status);
        $this->assertEquals(50000, $this->supplier->balance());

        $this->post(route('purchases.payments.store', $purchase), ['payment_amount' => 50000, 'payment_date' => '2026-09-26']);
        $this->assertSame('paid', $purchase->fresh()->payment_status);
        $this->assertEquals(0, $this->supplier->balance());

        // Payments must be removed before the stock can be taken back out.
        $this->put(route('purchases.update', $purchase), ['purchase_date' => '2026-09-20', 'status' => 'cancelled'])
            ->assertSessionHasErrors('status');
        $this->assertSame('received', $purchase->fresh()->status);
    }

    public function test_ordered_purchase_takes_no_payment(): void
    {
        $this->createPurchase('ordered', 20000);
        $purchase = Purchase::sole();

        $this->assertSame(0, Transaction::count());
        $this->post(route('purchases.payments.store', $purchase), ['payment_amount' => 1000, 'payment_date' => '2026-09-26'])
            ->assertSessionHasErrors('payment_amount');
    }

    public function test_supplier_payment_spreads_over_purchases(): void
    {
        $this->createPurchase('received');
        $this->createPurchase('received');

        $this->post(route('suppliers.payments.store', $this->supplier), ['apply_to' => 'auto', 'payment_amount' => 100000, 'payment_date' => '2026-09-26'])
            ->assertRedirect(route('suppliers.show', $this->supplier));

        $this->assertSame(['paid', 'partial'], Purchase::orderBy('id')->pluck('payment_status')->all());
        $this->assertEquals(40000, $this->supplier->balance());
    }

    public function test_seeded_data_is_consistent(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (Customer::with('orders.items', 'orders.transactions')->get() as $customer) {
            $this->assertEquals($customer->orders->sum(fn ($o) => $o->balanceDue()), $customer->balance(), $customer->name);
        }

        $hassan = Order::where('order_number', 'ORD-1040')->with('items', 'transactions')->sole();
        $this->assertEquals(40000, $hassan->amountPaid());
        $this->assertSame('partial', $this->saleStatus($hassan));
    }
}
