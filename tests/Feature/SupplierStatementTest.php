<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SupplierStatementTest extends TestCase
{
    use RefreshDatabase;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-20 12:00:00');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $this->supplier = Supplier::create(['name' => 'Al-Rahim Base Oil Traders', 'phone' => '0321-2456789', 'address' => 'Korangi Industrial Area, Karachi']);
        $product = Product::create(['name' => 'Jesco ATF Dextron DX-III 1L', 'sku' => 'JSC-ATF-1002', 'cost_price' => 700, 'selling_price' => 1000, 'stock_quantity' => 50]);

        $purchase = function (string $date, string $status, int $qty) use ($product) {
            $this->post(route('purchases.store'), [
                'supplier_id' => $this->supplier->id, 'purchase_date' => $date, 'status' => $status,
                'items' => [['product_id' => $product->id, 'quantity' => $qty, 'unit_cost' => 700]],
            ]);

            return Purchase::latest('id')->first();
        };

        // September: Rs. 70,000 received, Rs. 30,000 paid -> Rs. 40,000 owed going into October
        $september = $purchase('2026-09-08', 'received', 100);
        $this->post(route('purchases.payments.store', $september), ['payment_amount' => 30000, 'payment_date' => '2026-09-15', 'payment_method' => 'bank', 'payment_reference' => 'IBFT 20481']);

        // October: Rs. 14,000 received, Rs. 50,000 paid by cheque, and one purchase still on order
        $purchase('2026-10-05', 'received', 20);
        $purchase('2026-10-18', 'ordered', 10);
        $this->post(route('suppliers.payments.store', $this->supplier), [
            'apply_to' => 'auto', 'payment_amount' => 50000, 'payment_date' => '2026-10-10', 'payment_method' => 'cheque', 'payment_reference' => 'Cheque no. 000981',
        ]);
    }

    public function test_statement_balances(): void
    {
        $page = $this->get(route('reports.show', ['report' => 'supplier-statement', 'supplier' => $this->supplier->id, 'period' => 'this_month']))->assertOk();

        $this->assertEquals(['opening' => 40000, 'billed' => 14000, 'paid' => 50000, 'closing' => 4000], $page->viewData('summary'));
        $this->assertSame([54000.0, 14000.0, 4000.0], $page->viewData('lines')->pluck('balance')->map(fn ($b) => (float) $b)->all());

        // Both October purchases are listed; the one still on order owes nothing yet.
        $this->assertCount(2, $page->viewData('bills'));
        $page->assertSee('You Owe')->assertSeeText('Purchase PO-5502 received')->assertSee('Ref: Cheque no. 000981')->assertSee('Ordered');
    }

    public function test_all_time_matches_the_supplier_balance(): void
    {
        $summary = $this->get(route('reports.show', ['report' => 'supplier-statement', 'supplier' => $this->supplier->id]))->viewData('summary');

        $this->assertEquals(['opening' => 0, 'billed' => 84000, 'paid' => 80000, 'closing' => 4000], $summary);
        $this->assertEquals($this->supplier->balance(), $summary['closing']);
    }

    public function test_csv_picker_and_button(): void
    {
        $this->get(route('reports.show', 'supplier-statement'))->assertOk()->assertSee('Choose a supplier to see their statement.');

        $csv = $this->get(route('reports.csv', ['report' => 'supplier-statement', 'supplier' => $this->supplier->id, 'period' => 'this_month']))->streamedContent();
        $this->assertStringContainsString('"Statement for Al-Rahim Base Oil Traders"', $csv);
        $this->assertStringContainsString(',"Closing balance",,,14000,50000,4000', $csv);
        $this->assertStringContainsString('Purchases', $csv);

        $this->get(route('suppliers.show', $this->supplier))->assertSee(route('reports.show', ['report' => 'supplier-statement', 'supplier' => $this->supplier->id]), false);
    }
}
