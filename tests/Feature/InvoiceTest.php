<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\AmountInWords;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_amounts_are_written_in_lakh_and_crore(): void
    {
        $this->assertSame('Ninety-Five Thousand Six Hundred Rupees Only', AmountInWords::rupees(95600));
        $this->assertSame('One Lakh Forty-One Thousand Ten Rupees Only', AmountInWords::rupees(141010));
        $this->assertSame('Two Crore Five Lakh Rupees Only', AmountInWords::rupees(20500000));
        $this->assertSame('One Rupee Only', AmountInWords::rupees(1));
        $this->assertSame('Twelve Rupees and Fifty Paisa Only', AmountInWords::rupees(12.5));
        $this->assertSame('Zero Rupees Only', AmountInWords::rupees(0));
    }

    public function test_invoice_shows_the_order_and_what_is_owed(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SALES]));

        $product = Product::create(['name' => 'Jesco Motor Engine Oil API CF-4/SJ 20W50 3L', 'sku' => 'JSC-ME-3004', 'selling_price' => 2950, 'cost_price' => 2250, 'stock_quantity' => 50]);
        $customer = Customer::create(['name' => 'Hassan Raza', 'company' => 'Raza Automotive Garage', 'phone' => '0345-1122334', 'address' => 'Baldia Town, Karachi']);
        $this->post(route('orders.store'), [
            'customer_id' => $customer->id, 'order_date' => '2026-09-20', 'status' => 'pending', 'amount_paid' => 20000,
            'items' => [['product_id' => $product->id, 'quantity' => 20, 'unit_price' => 2950]],
        ]);
        $order = Order::sole();

        $this->get(route('orders.show', $order))->assertSee(route('orders.invoice', $order));

        $this->get(route('orders.invoice', $order))->assertOk()
            ->assertSee('INV-'.str_replace('ORD-', '', $order->order_number))
            ->assertSee('PAK OIL LUBRICANTS')->assertSee('0320-8853243')
            ->assertSee('Raza Automotive Garage')->assertSee('JSC-ME-3004')
            ->assertSee('Rs. 59,000')->assertSee('Fifty-Nine Thousand Rupees Only')
            ->assertSee('Partially Paid')->assertSee('Rs. 39,000');
    }

    public function test_warehouse_staff_cannot_open_invoices(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $order = Order::create(['order_number' => 'ORD-1001', 'customer_id' => Customer::create(['name' => 'Bilal Khan'])->id, 'order_date' => '2026-09-20', 'status' => 'cancelled']);

        $this->get(route('orders.invoice', $order))->assertOk()->assertSee('Cancelled');

        $this->actingAs(User::factory()->create(['role' => User::ROLE_WAREHOUSE]));
        $this->get(route('orders.invoice', $order))->assertForbidden();
    }
}
