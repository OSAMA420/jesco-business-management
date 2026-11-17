<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProductPriceTest extends TestCase
{
    use RefreshDatabase;

    private const NOTE = 'sold without a cost price';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-17 12:00:00');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
    }

    private function productWithoutPrices(): Product
    {
        $this->post(route('products.store'), [
            'name' => 'Jesco Motor Engine Oil API SF/CF 20W60 1L',
            'cost_price' => '',
            'selling_price' => '',
            'stock_quantity' => 40,
            'reorder_level' => 10,
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        return Product::latest('id')->first();
    }

    private function sell(Product $product, int $qty, float $rate): void
    {
        $this->post(route('orders.store'), [
            'customer_id' => Customer::firstOrCreate(['name' => 'Al-Madina Autos'])->id,
            'order_date' => '2026-11-17',
            'status' => 'pending',
            'amount_paid' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => $qty, 'unit_price' => $rate]],
        ])->assertSessionHasNoErrors();
    }

    public function test_a_product_can_be_added_without_prices(): void
    {
        $product = $this->productWithoutPrices();

        $this->assertNull($product->cost_price);
        $this->assertNull($product->selling_price);
        $this->get(route('products.index'))->assertOk()->assertSee($product->name);
        $this->get(route('orders.create'))->assertOk()->assertSee('No selling price set, type the rate');
        $this->get(route('purchases.create'))->assertOk()->assertSee('No cost set yet, type the cost');
    }

    public function test_an_order_still_needs_a_rate(): void
    {
        $product = $this->productWithoutPrices();

        $this->post(route('orders.store'), [
            'customer_id' => Customer::create(['name' => 'Al-Madina Autos'])->id,
            'order_date' => '2026-11-17',
            'status' => 'pending',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => '']],
        ])->assertSessionHasErrors('items.0.unit_price');
    }

    public function test_profit_pages_warn_about_sales_without_a_cost(): void
    {
        $product = $this->productWithoutPrices();
        $this->sell($product, 2, 1150);

        $this->assertNull(OrderItem::first()->unit_cost);

        $this->get(route('finance.index'))->assertOk()->assertSee(self::NOTE)->assertSee('Rs. 2,300 of sales');
        $this->get(route('reports.show', 'profit-loss'))->assertOk()->assertSee(self::NOTE)->assertSee($product->name.' (cost not set)');
        $this->get(route('dashboard'))->assertOk()->assertSee('Higher than real: 1 product sold without a cost');
    }

    public function test_adding_the_cost_later_fills_earlier_sales(): void
    {
        $product = $this->productWithoutPrices();
        $this->sell($product, 2, 1150);

        $this->put(route('products.update', $product), [
            'name' => $product->name,
            'cost_price' => 880,
            'selling_price' => '',
            'stock_quantity' => $product->fresh()->stock_quantity,
            'reorder_level' => 10,
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(880, OrderItem::first()->unit_cost);
        $this->get(route('finance.index'))->assertDontSee(self::NOTE);
    }

    public function test_receiving_a_purchase_fills_earlier_sales(): void
    {
        $product = $this->productWithoutPrices();
        $this->sell($product, 2, 1150);

        $this->post(route('purchases.store'), [
            'supplier_id' => Supplier::create(['name' => 'Jesco Lubricants Plant'])->id,
            'purchase_date' => '2026-11-17',
            'status' => 'received',
            'items' => [['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 870]],
        ])->assertSessionHasNoErrors();

        $this->assertEquals(870, $product->fresh()->cost_price);
        $this->assertEquals(870, OrderItem::first()->unit_cost);
    }

    public function test_stock_value_leaves_out_products_without_prices(): void
    {
        $this->productWithoutPrices();
        Product::create(['name' => 'Jesco ATF Dextron DX-III 1L', 'cost_price' => 700, 'selling_price' => 1000, 'stock_quantity' => 5]);

        $page = $this->get(route('reports.show', 'stock'))->assertOk();

        $page->assertSee('Rs. 3,500')->assertSee('Rs. 5,000');
        $page->assertSee('1 in-stock product without a cost left out');
        $page->assertSee('1 in-stock product without a price left out');
        $page->assertSee('Not set');
        $this->get(route('dashboard'))->assertOk()->assertSee('1 product without a cost left out');
    }
}
