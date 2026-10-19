<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FinanceAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-20 12:00:00');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
    }

    private function openings(array $amounts, string $date = '2026-10-01')
    {
        return $this->post(route('finance.opening.store'), ['opening_date' => $date, 'opening' => $amounts]);
    }

    /** A Rs. 10,000 order paid Rs. 4,000 in cash and Rs. 3,000 by cheque, plus a Rs. 1,500 cash expense. */
    private function someMoneyMoving(): void
    {
        $product = Product::create(['name' => 'Jesco ATF Dextron DX-III 1L', 'sku' => 'JSC-ATF-1002', 'cost_price' => 700, 'selling_price' => 1000, 'stock_quantity' => 50]);
        $this->post(route('orders.store'), [
            'customer_id' => Customer::create(['name' => 'Hassan Raza'])->id, 'order_date' => '2026-10-10', 'status' => 'pending',
            'amount_paid' => 4000, 'paid_method' => 'cash',
            'items' => [['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 1000]],
        ]);
        $this->post(route('orders.payments.store', Order::sole()), ['payment_amount' => 3000, 'payment_date' => '2026-10-15', 'payment_method' => 'cheque', 'payment_reference' => 'Cheque no. 004512']);
        $this->post(route('finance.expenses.store'), ['expense_date' => '2026-10-12', 'category' => 'transport', 'amount' => 1500, 'method' => 'cash', 'description' => 'Loader rickshaw']);
    }

    public function test_opening_balances_are_saved_and_replaced(): void
    {
        $this->openings(['cash' => 25000, 'bank' => 150000, 'wallet' => ''])->assertRedirect(route('finance.index'))->assertSessionHas('status', 'Opening balances saved.');
        $this->assertSame(2, Transaction::where('type', 'opening')->count());

        // Saving again replaces, it doesn't add up; zero removes that account's opening.
        $this->openings(['cash' => 30000, 'bank' => 0, 'wallet' => 5000], '2026-09-30');
        $this->assertEquals(
            ['cash' => 30000.0, 'wallet' => 5000.0],
            Transaction::where('type', 'opening')->orderBy('payment_method')->pluck('amount', 'payment_method')->map(fn ($a) => (float) $a)->all()
        );
        $this->assertSame(['2026-09-30'], Transaction::where('type', 'opening')->get()->map(fn ($t) => $t->transaction_date->toDateString())->unique()->values()->all());
    }

    public function test_account_balances_use_openings_and_payment_methods(): void
    {
        $this->openings(['cash' => 25000, 'bank' => 150000]);
        $this->someMoneyMoving();

        $accounts = $this->get(route('finance.index'))->assertOk()->viewData('accounts');

        $this->assertEquals(25000 + 4000 - 1500, $accounts['list']['cash']['balance']);
        $this->assertEquals(150000 + 3000, $accounts['list']['bank']['balance']);   // a cheque lands in the bank
        $this->assertEquals(0, $accounts['list']['wallet']['balance']);
        $this->assertEquals(180500, $accounts['total']);
        $this->assertSame('2026-10-01', $accounts['openingDate']->toDateString());
        $this->assertSame(0, $accounts['unassignedCount']);
    }

    public function test_cash_book_runs_day_by_day(): void
    {
        $this->openings(['cash' => 25000]);
        $this->someMoneyMoving();

        $book = $this->get(route('finance.index', ['tab' => 'cashbook', 'account' => 'cash', 'period' => 'custom', 'from' => '2026-10-11', 'to' => '2026-10-20']))
            ->assertOk()->viewData('book');

        // Opening of the period = Rs. 25,000 opening + Rs. 4,000 received on the 10th.
        $this->assertEquals(29000, $book['opening']);
        $this->assertEquals(0, $book['in']);
        $this->assertEquals(1500, $book['out']);
        $this->assertEquals(27500, $book['closing']);
        $this->assertCount(1, $book['days']);
        $this->assertSame('2026-10-12', $book['days'][0]->date->toDateString());

        // The bank book only shows the cheque.
        $bank = $this->get(route('finance.index', ['tab' => 'cashbook', 'account' => 'bank']))->viewData('book');
        $this->assertEquals(3000, $bank['closing']);
        $this->get(route('finance.index', ['tab' => 'cashbook', 'account' => 'bank']))->assertSee('Ref: Cheque no. 004512');
    }

    public function test_overview_breaks_money_down_and_shows_profit(): void
    {
        $this->someMoneyMoving();

        $page = $this->get(route('finance.index', ['period' => 'this_month']))->assertOk();
        $flows = $page->viewData('flows');
        $this->assertEquals(4000, $flows['in']['cash']);
        $this->assertEquals(3000, $flows['in']['bank']);
        $this->assertEquals(1500, $flows['expenses']['cash']);
        $this->assertEquals(['revenue' => 10000, 'cogs' => 7000, 'gross' => 3000, 'expenses' => 1500, 'net' => 1500], $page->viewData('profit'));
        $page->assertSee('Money You Have Now')->assertSee('Net Cash Flow')->assertSee('Cash Book');
    }

    public function test_bad_opening_input_is_rejected(): void
    {
        $this->openings(['cash' => -500])->assertSessionHasErrors('opening.cash');
        $this->openings(['cash' => 500], '2026-12-01')->assertSessionHasErrors('opening_date');
        $this->assertSame(0, Transaction::where('type', 'opening')->count());
    }
}
