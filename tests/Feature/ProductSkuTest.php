<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSkuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
    }

    private function productData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jesco Motor Engine Oil API SF/CF 20W60 1L',
            'sku' => '',
            'cost_price' => 880,
            'selling_price' => 1150,
            'stock_quantity' => 15,
            'reorder_level' => 30,
            'is_active' => '1',
        ], $overrides);
    }

    public function test_products_can_be_added_without_a_sku(): void
    {
        $this->post(route('products.store'), $this->productData())->assertSessionHasNoErrors();
        $this->post(route('products.store'), $this->productData(['name' => 'Jesco MP Gold Gear Oil GL-4 90W140 1L']))->assertSessionHasNoErrors();

        $this->assertSame(2, Product::whereNull('sku')->count());
    }

    public function test_a_filled_in_sku_must_still_be_unique(): void
    {
        Product::create(['name' => 'Jesco ATF Dextron DX-III 1L', 'sku' => 'JSC-ATF-1002']);

        $this->post(route('products.store'), $this->productData(['sku' => 'JSC-ATF-1002']))->assertSessionHasErrors('sku');
    }

    public function test_pages_show_a_product_without_a_sku(): void
    {
        $product = Product::create(['name' => 'Jesco Motor Engine Oil API CF-4/SJ 20W50 3L', 'selling_price' => 2950, 'stock_quantity' => 65]);
        Customer::create(['name' => 'Al-Madina Autos']);

        $this->get(route('products.index'))->assertOk()->assertSee($product->name);
        $this->get(route('inventory.index'))->assertOk()->assertSee($product->name.' &mdash;', false);
        $this->get(route('products.edit', $product))->assertOk()->assertSee('SKU (optional)');
        $this->get(route('orders.create'))->assertOk();
        $this->get(route('purchases.create'))->assertOk();
    }
}
