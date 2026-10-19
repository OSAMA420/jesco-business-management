<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $this->customer = Customer::create(['name' => 'Hassan Raza']);
        $this->product = Product::create(['name' => 'Jesco ATF Dextron DX-III 1L', 'sku' => 'JSC-ATF-1002', 'cost_price' => 700, 'selling_price' => 1000, 'stock_quantity' => 500]);
    }

    private function order(array $extra = []): Order
    {
        $this->post(route('orders.store'), $extra + [
            'customer_id' => $this->customer->id, 'order_date' => '2026-10-15', 'status' => 'pending',
            'items' => [['product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 1000]],
        ])->assertSessionHasNoErrors();

        return Order::latest('id')->first();
    }

    public function test_order_payment_records_how_it_was_paid_and_shows_it(): void
    {
        $order = $this->order();

        $this->post(route('orders.payments.store', $order), [
            'payment_amount' => 4000, 'payment_date' => '2026-10-16', 'payment_method' => 'wallet', 'payment_reference' => 'TID 0123456789',
        ])->assertSessionHasNoErrors();

        $payment = Transaction::where('type', 'payment_in')->sole();
        $this->assertSame('wallet', $payment->payment_method);
        $this->assertSame('TID 0123456789', $payment->reference_no);

        $customerPage = $this->get(route('customers.show', $this->customer))->assertSee('Paid Via')->assertSee('JazzCash / Easypaisa')->assertSee('Ref: TID 0123456789');
        $customerPage->assertSee('Total Paid')->assertSee('40% of total spent');
        $this->assertEquals(4000, $customerPage->viewData('totalPaid'));
        $this->get(route('orders.show', $order))->assertSee('JazzCash / Easypaisa');
        $this->get(route('orders.invoice', $order))->assertSee('JazzCash / Easypaisa')->assertSee('(TID 0123456789)', false);
        $this->get(route('finance.index', ['tab' => 'transactions']))->assertSee('JazzCash / Easypaisa · Ref: TID 0123456789');
    }

    public function test_method_is_required_and_must_be_a_known_one(): void
    {
        $order = $this->order();

        $this->post(route('orders.payments.store', $order), ['payment_amount' => 1000, 'payment_date' => '2026-10-16'])
            ->assertSessionHasErrors('payment_method');
        $this->post(route('orders.payments.store', $order), ['payment_amount' => 1000, 'payment_date' => '2026-10-16', 'payment_method' => 'bitcoin'])
            ->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Transaction::where('type', 'payment_in')->count());
    }

    public function test_paid_at_order_time_keeps_the_method(): void
    {
        $this->order(['amount_paid' => 2500, 'paid_method' => 'cheque', 'paid_reference' => 'Cheque no. 004512']);

        $payment = Transaction::where('type', 'payment_in')->sole();
        $this->assertSame('cheque', $payment->payment_method);
        $this->assertSame('Cheque no. 004512', $payment->reference_no);
    }

    public function test_a_customer_payment_split_over_bills_keeps_the_method_on_each_part(): void
    {
        $this->order();
        $this->order();

        $this->post(route('customers.payments.store', $this->customer), [
            'apply_to' => 'auto', 'payment_amount' => 15000, 'payment_date' => '2026-10-17', 'payment_method' => 'bank', 'payment_reference' => 'IBFT 20481',
        ])->assertSessionHasNoErrors();

        $payments = Transaction::where('type', 'payment_in')->get();
        $this->assertCount(2, $payments);
        $this->assertSame(['bank'], $payments->pluck('payment_method')->unique()->values()->all());
        $this->assertSame(['IBFT 20481'], $payments->pluck('reference_no')->unique()->values()->all());
    }

    public function test_supplier_payments_show_the_method_too(): void
    {
        $supplier = Supplier::create(['name' => 'Al-Rahim Base Oil Traders']);
        $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id, 'purchase_date' => '2026-10-10', 'status' => 'received',
            'items' => [['product_id' => $this->product->id, 'quantity' => 10, 'unit_cost' => 700]],
        ]);

        $this->post(route('purchases.payments.store', Purchase::sole()), [
            'payment_amount' => 7000, 'payment_date' => '2026-10-12', 'payment_method' => 'cheque', 'payment_reference' => 'Cheque no. 000981',
        ])->assertSessionHasNoErrors();

        $this->get(route('suppliers.show', $supplier))->assertSee('Paid Via')->assertSee('Cheque')->assertSee('Ref: Cheque no. 000981');
    }

    public function test_payment_forms_offer_every_method(): void
    {
        $order = $this->order();

        $page = $this->get(route('orders.show', $order))->assertOk();
        foreach (Transaction::PAYMENT_METHODS as $label) {
            $page->assertSee($label);
        }
        $this->get(route('customers.show', $this->customer))->assertSee('Reference No.');
        $this->get(route('orders.create'))->assertSee('paid_method', false);
    }
}
