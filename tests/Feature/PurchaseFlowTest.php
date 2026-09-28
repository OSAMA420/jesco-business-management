<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    private Supplier $supplier;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->supplier = Supplier::create(['name' => 'Al-Rahim Base Oil Traders', 'phone' => '0321-2456789']);
        $this->product = Product::create([
            'name' => 'Jesco Motor Engine Oil API SF/CF 20W60 1L',
            'sku' => 'JSC-ME-1001',
            'cost_price' => 690,
            'selling_price' => 950,
            'stock_quantity' => 10,
        ]);
    }

    private function createPurchase(string $status, string $paymentStatus = 'unpaid', int $qty = 100, int $cost = 700)
    {
        return $this->post(route('purchases.store'), [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => '2026-09-26',
            'status' => $status,
            'payment_status' => $paymentStatus,
            'items' => [['product_id' => $this->product->id, 'quantity' => $qty, 'unit_cost' => $cost]],
        ]);
    }

    public function test_received_purchase_adds_stock_and_creates_payable(): void
    {
        $this->createPurchase('received')->assertRedirect();

        $purchase = Purchase::firstOrFail();
        $this->assertSame('PO-5501', $purchase->purchase_number);
        $this->assertSame(110, $this->product->fresh()->stock_quantity);
        $this->assertSame(1, $purchase->stockMovements()->where('type', 'in')->count());
        $this->assertEquals(-70000, $purchase->transactions()->sole()->amount);
        $this->assertEquals(70000, $this->supplier->balance());
    }

    public function test_ordered_purchase_changes_nothing_until_received(): void
    {
        $this->createPurchase('ordered', 'partial');
        $purchase = Purchase::firstOrFail();

        $this->assertSame(10, $this->product->fresh()->stock_quantity);
        $this->assertSame(0, $purchase->transactions()->count());
        $this->assertEquals(0, $this->supplier->balance());

        $this->put(route('purchases.update', $purchase), ['purchase_date' => '2026-09-27', 'status' => 'received'])->assertRedirect();

        $this->assertSame(110, $this->product->fresh()->stock_quantity);
        // No payment has been recorded yet, so it starts unpaid.
        $transaction = $purchase->transactions()->sole();
        $this->assertSame('unpaid', $transaction->status);
        $this->assertEquals(70000, $this->supplier->balance());
    }

    public function test_cancelling_a_received_purchase_reverses_stock_and_payable(): void
    {
        $this->createPurchase('received');
        $purchase = Purchase::firstOrFail();

        $this->put(route('purchases.update', $purchase), ['purchase_date' => '2026-09-26', 'status' => 'cancelled'])->assertRedirect();

        $this->assertSame(10, $this->product->fresh()->stock_quantity);
        $this->assertSame(0, $purchase->transactions()->count());
        $this->assertEquals(0, $this->supplier->balance());
    }

    public function test_cannot_unreceive_when_stock_was_already_sold(): void
    {
        $this->createPurchase('received');
        $purchase = Purchase::firstOrFail();
        $this->product->update(['stock_quantity' => 30]); // most of it was sold

        $this->put(route('purchases.update', $purchase), ['purchase_date' => '2026-09-26', 'status' => 'cancelled'])
            ->assertSessionHasErrors('status');
        $this->assertSame('received', $purchase->fresh()->status);
        $this->assertSame(30, $this->product->fresh()->stock_quantity);

        $this->delete(route('purchases.destroy', $purchase))->assertSessionHas('error');
        $this->assertNotNull($purchase->fresh());
    }

    public function test_deleting_a_received_purchase_removes_stock_and_records(): void
    {
        $this->createPurchase('received');
        $purchase = Purchase::firstOrFail();

        $this->delete(route('purchases.destroy', $purchase))->assertRedirect(route('purchases.index'));

        $this->assertNull($purchase->fresh());
        $this->assertSame(10, $this->product->fresh()->stock_quantity);
        $this->assertSame(0, Transaction::count());
    }

    public function test_supplier_payment_reduces_balance_and_is_capped(): void
    {
        $this->createPurchase('received');

        $this->post(route('suppliers.payments.store', $this->supplier), ['apply_to' => 'auto', 'payment_amount' => 90000, 'payment_date' => '2026-09-26'])
            ->assertSessionHasErrors('payment_amount');

        $this->post(route('suppliers.payments.store', $this->supplier), ['apply_to' => 'auto', 'payment_amount' => 30000, 'payment_date' => '2026-09-26'])
            ->assertRedirect(route('suppliers.show', $this->supplier));

        $this->assertEquals(40000, $this->supplier->balance());
        $this->assertSame('payment_out', Transaction::latest('id')->first()->type);
    }

    public function test_supplier_with_history_cannot_be_deleted(): void
    {
        $this->createPurchase('ordered');
        $this->delete(route('suppliers.destroy', $this->supplier))->assertSessionHas('error');
        $this->assertNotNull($this->supplier->fresh());

        $fresh = Supplier::create(['name' => 'Sind Oil Distributors']);
        $this->delete(route('suppliers.destroy', $fresh))->assertRedirect(route('suppliers.index'));
        $this->assertNull($fresh->fresh());
    }

    public function test_pages_render(): void
    {
        $this->createPurchase('received', 'partial');
        $purchase = Purchase::firstOrFail();

        foreach ([
            route('suppliers.index'), route('suppliers.create'), route('suppliers.show', $this->supplier),
            route('suppliers.edit', $this->supplier), route('purchases.index'), route('purchases.create'),
            route('purchases.show', $purchase), route('purchases.edit', $purchase),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(route('purchases.index', ['status' => 'received', 'search' => 'rahim']))->assertSee('PO-5501');

        $order = \App\Models\Order::create(['order_number' => 'ORD-1001', 'customer_id' => \App\Models\Customer::create(['name' => 'Bilal Khan'])->id, 'order_date' => '2026-09-26', 'status' => 'pending']);
        foreach ([route('orders.index'), route('orders.create'), route('orders.show', $order), route('customers.show', $order->customer_id)] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_sales_role_cannot_reach_purchases(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SALES]));

        $this->get(route('suppliers.index'))->assertForbidden();
        $this->get(route('purchases.index'))->assertForbidden();
    }
}
