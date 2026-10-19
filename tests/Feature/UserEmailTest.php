<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEmailTest extends TestCase
{
    use RefreshDatabase;

    private function newUser(string $email)
    {
        return $this->post(route('users.store'), ['name' => 'Osama Ahmed', 'email' => $email, 'password' => 'Jesco@2026', 'role' => User::ROLE_SALES]);
    }

    public function test_a_deleted_users_email_can_be_used_again(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->newUser('osama@example.com')->assertSessionHasNoErrors();
        $this->delete(route('users.destroy', User::where('email', 'osama@example.com')->sole()))->assertSessionHas('status', 'User removed.');
        $this->assertDatabaseMissing('users', ['email' => 'osama@example.com']);

        $this->newUser('osama@example.com')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'osama@example.com']);
    }

    public function test_duplicate_email_says_who_has_it(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $this->newUser('osama@example.com');

        $this->newUser('osama@example.com')->assertSessionHasErrors([
            'email' => 'Osama Ahmed already uses this email. Edit or delete that user in the Users list, or use a different email.',
        ]);
    }
}
