<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FinancePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-29 12:00:00');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_MANAGER]));
    }

    private function expense(string $date, string $category, float $amount, string $description = 'Test expense'): void
    {
        $this->post(route('finance.expenses.store'), [
            'expense_date' => $date, 'category' => $category, 'amount' => $amount, 'method' => 'cash', 'description' => $description,
        ])->assertSessionHasNoErrors();
    }

    /** Rs. 10,000 order paid partly, and a Rs. 70,000 received purchase paid partly. */
    private function trade(): void
    {
        $product = Product::create(['name' => 'Jesco ATF Dextron DX-III 1L', 'sku' => 'JSC-ATF-1002', 'selling_price' => 1000, 'cost_price' => 700, 'stock_quantity' => 50]);

        $this->post(route('orders.store'), [
            'customer_id' => Customer::create(['name' => 'Hassan Raza'])->id, 'order_date' => '2026-09-20', 'status' => 'pending', 'amount_paid' => 4000,
            'items' => [['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 1000]],
        ]);
        $this->post(route('purchases.store'), [
            'supplier_id' => Supplier::create(['name' => 'Al-Rahim Base Oil Traders'])->id, 'purchase_date' => '2026-08-10', 'status' => 'received', 'amount_paid' => 30000,
            'items' => [['product_id' => $product->id, 'quantity' => 100, 'unit_cost' => 700]],
        ]);
    }

    public function test_overview_totals_come_from_payments_and_expenses(): void
    {
        $this->trade();
        $this->expense('2026-09-05', 'rent', 18000);
        $this->expense('2026-09-25', 'utilities', 14200);
        $this->expense('2026-08-15', 'rent', 18000);

        $page = $this->get(route('finance.index', ['period' => 'this_month']))->assertOk();
        $summary = $page->viewData('summary');

        $this->assertEquals(4000, $summary['cash_in']);             // customer payment in September
        $this->assertEquals(32200, $summary['cash_out']);           // September expenses only; the purchase payment was August
        $this->assertEquals(-28200, $summary['net']);
        $this->assertEquals(6000, $summary['receivable']);          // balances ignore the period
        $this->assertEquals(40000, $summary['payable']);

        $months = $page->viewData('monthly')->keyBy('month');
        $this->assertSame(['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'], $page->viewData('monthly')->pluck('month')->all());
        $this->assertEquals(48000, $months['Aug']['out']);           // 30,000 to supplier + 18,000 rent
        $this->assertEquals(4000, $months['Sep']['in']);

        $this->assertSame(['Rent', 'Electricity & Utilities'], $page->viewData('byCategory')->pluck('label')->all());
        $page->assertSee('Hassan Raza')->assertSee('Al-Rahim Base Oil Traders');
    }

    public function test_expenses_can_be_added_edited_and_deleted(): void
    {
        $this->expense('2026-09-10', 'rent', 18000, 'Warehouse rent, Korangi');
        $expense = Transaction::where('type', 'expense')->sole();
        $this->assertEquals(-18000, $expense->amount);
        $this->assertSame('rent', $expense->category);

        $this->put(route('finance.expenses.update', $expense), [
            'expense_date' => '2026-09-11', 'category' => 'rent', 'amount' => 20000, 'method' => 'cheque', 'description' => 'Warehouse rent, Korangi',
        ])->assertRedirect(route('finance.index', ['tab' => 'expenses']));
        $this->assertEquals(-20000, $expense->fresh()->amount);
        $this->assertSame('cheque', $expense->fresh()->payment_method);

        $this->get(route('finance.index', ['tab' => 'expenses']))->assertSee('Warehouse rent, Korangi')->assertSee('Rs. 20,000');

        $this->delete(route('finance.expenses.destroy', $expense));
        $this->assertSame(0, Transaction::count());
    }

    public function test_expense_validation_and_only_expenses_are_editable(): void
    {
        $this->post(route('finance.expenses.store'), ['expense_date' => '2026-09-10', 'category' => 'lunch', 'amount' => 0, 'method' => 'cash'])
            ->assertSessionHasErrors(['category', 'amount', 'description']);

        $this->trade();
        $payment = Transaction::where('type', 'payment_in')->first();
        $this->delete(route('finance.expenses.destroy', $payment))->assertNotFound();
        $this->assertNotNull($payment->fresh());
    }

    public function test_transactions_and_expenses_filters(): void
    {
        $this->trade();
        $this->expense('2026-09-25', 'utilities', 14200, 'K-Electric bill');
        $this->expense('2026-09-15', 'maintenance', 6800, 'Forklift repair');
        $order = Order::sole();

        $this->get(route('finance.index', ['tab' => 'transactions', 'type' => 'expense']))
            ->assertSee('Forklift repair')->assertDontSee('Paid at the time of order');
        $this->get(route('finance.index', ['tab' => 'transactions', 'search' => $order->order_number]))
            ->assertSee('Paid at the time of order')->assertDontSee('Forklift repair');
        $this->get(route('finance.index', ['tab' => 'transactions', 'search' => 'Al-Rahim']))
            ->assertSee('Paid at the time of purchase')->assertDontSee('Paid at the time of order');

        $page = $this->get(route('finance.index', ['tab' => 'expenses', 'category' => 'maintenance']));
        $page->assertSee('Forklift repair')->assertDontSee('>K-Electric bill<', false);
        $this->assertEquals(6800, $page->viewData('expenseTotal'));
    }

    public function test_sales_role_cannot_open_finance(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SALES]));

        $this->get(route('finance.index'))->assertForbidden();
    }
}
