<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickCustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_order_form_offers_add_new_customer(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SALES]));
        Customer::create(['name' => 'Bilal Khan', 'company' => 'Al-Madina Auto Workshop']);

        $this->get(route('orders.create'))->assertOk()
            ->assertSee('+ Add new customer')
            ->assertSee('Add New Customer')
            ->assertSee('Al-Madina Auto Workshop');
    }

    public function test_customer_can_be_added_from_the_order_form(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SALES]));

        $this->postJson(route('customers.quick-store'), [
            'name' => 'Imran Siddiqui', 'company' => 'Siddiqui Autos', 'phone' => '0312-4455667', 'email' => '', 'address' => 'Orangi Town, Karachi',
        ])->assertCreated()->assertJson(['name' => 'Imran Siddiqui', 'company' => 'Siddiqui Autos']);

        $this->assertDatabaseHas('customers', ['name' => 'Imran Siddiqui', 'phone' => '0312-4455667']);
    }

    public function test_invalid_details_come_back_as_errors(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SALES]));

        $this->postJson(route('customers.quick-store'), ['name' => '', 'email' => 'not-an-email'])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'email']);

        $this->assertSame(0, Customer::count());
    }

    public function test_warehouse_staff_cannot_add_customers(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_WAREHOUSE]));

        $this->postJson(route('customers.quick-store'), ['name' => 'Someone'])->assertForbidden();
    }
}
