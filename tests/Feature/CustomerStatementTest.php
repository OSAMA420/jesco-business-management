<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CustomerStatementTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-20 12:00:00');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_MANAGER]));
        $this->customer = Customer::create(['name' => 'Hassan Raza', 'company' => 'Raza Automotive Garage', 'phone' => '0345-1122334']);
        $product = Product::create(['name' => 'Jesco ATF Dextron DX-III 1L', 'sku' => 'JSC-ATF-1002', 'cost_price' => 700, 'selling_price' => 1000, 'stock_quantity' => 500]);

        $order = function (string $date, int $qty) use ($product) {
            $this->post(route('orders.store'), [
                'customer_id' => $this->customer->id, 'order_date' => $date, 'status' => 'pending',
                'items' => [['product_id' => $product->id, 'quantity' => $qty, 'unit_price' => 1000]],
            ]);

            return Order::latest('id')->first();
        };

        // September: Rs. 10,000 billed, Rs. 4,000 paid  -> Rs. 6,000 owed going into October
        $september = $order('2026-09-05', 10);
        $this->post(route('orders.payments.store', $september), ['payment_amount' => 4000, 'payment_date' => '2026-09-10', 'payment_method' => 'cash']);

        // October: Rs. 5,000 billed, Rs. 7,000 paid by cheque
        $order('2026-10-02', 5);
        $this->post(route('customers.payments.store', $this->customer), [
            'apply_to' => 'auto', 'payment_amount' => 7000, 'payment_date' => '2026-10-12', 'payment_method' => 'cheque', 'payment_reference' => 'Cheque no. 004512',
        ]);
    }

    public function test_statement_has_opening_running_and_closing_balances(): void
    {
        $page = $this->get(route('reports.show', ['report' => 'customer-statement', 'customer' => $this->customer->id, 'period' => 'this_month']))->assertOk();

        $this->assertEquals(['opening' => 6000, 'billed' => 5000, 'paid' => 7000, 'closing' => 4000], $page->viewData('summary'));

        // The Rs. 7,000 cheque clears the September bill first, then part of October's: two payment lines.
        $lines = $page->viewData('lines');
        $this->assertSame([11000.0, 5000.0, 4000.0], $lines->pluck('balance')->map(fn ($b) => (float) $b)->all());
        $this->assertSame(['', 'Cheque', 'Cheque'], $lines->map(fn ($l) => $l->method ?? '')->all());

        $page->assertSee('Statement For')->assertSee('Raza Automotive Garage')->assertSee('Ref: Cheque no. 004512')
            ->assertSee('Opening balance')->assertSee('Closing balance');
        $this->assertCount(1, $page->viewData('bills'));
    }

    public function test_all_time_starts_from_zero(): void
    {
        $summary = $this->get(route('reports.show', ['report' => 'customer-statement', 'customer' => $this->customer->id]))->viewData('summary');

        $this->assertEquals(['opening' => 0, 'billed' => 15000, 'paid' => 11000, 'closing' => 4000], $summary);
        $this->assertEquals($this->customer->balance(), $summary['closing']);
    }

    public function test_csv_and_picker(): void
    {
        $this->get(route('reports.show', 'customer-statement'))->assertOk()->assertSee('Choose a customer to see their statement.');

        $csv = $this->get(route('reports.csv', ['report' => 'customer-statement', 'customer' => $this->customer->id, 'period' => 'this_month']))->streamedContent();
        $this->assertStringContainsString('"Statement for Hassan Raza"', $csv);
        $this->assertStringContainsString(',"Opening balance",,,,,6000', $csv);
        $this->assertStringContainsString('"Cheque no. 004512"', $csv);
        $this->assertStringContainsString(',"Closing balance",,,5000,7000,4000', $csv);

        $this->get(route('customers.show', $this->customer))->assertSee(route('reports.show', ['report' => 'customer-statement', 'customer' => $this->customer->id]), false);
    }

    public function test_sales_staff_have_no_statement_button(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SALES]));

        $this->get(route('customers.show', $this->customer))->assertOk()->assertDontSee('customer-statement');
        $this->get(route('reports.show', 'customer-statement'))->assertForbidden();
    }
}
