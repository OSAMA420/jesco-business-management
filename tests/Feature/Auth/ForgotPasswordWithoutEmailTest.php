<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Until the final hosting provides a mail server, password resets go through an admin.
 */
class ForgotPasswordWithoutEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_asks_users_to_contact_their_admin(): void
    {
        config(['mail.default' => 'log']);

        $this->get('/forgot-password')->assertOk()
            ->assertSee('Please contact your admin to reset your password.')
            ->assertDontSee('Email Password Reset Link');
    }

    public function test_no_reset_link_is_sent_or_claimed(): void
    {
        config(['mail.default' => 'log']);
        Notification::fake();
        $user = User::factory()->create();

        $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect('/forgot-password')
            ->assertSessionMissing('status');

        Notification::assertNotSentTo($user, ResetPassword::class);
    }

    public function test_email_form_comes_back_once_a_mailer_is_set(): void
    {
        config(['mail.default' => 'smtp']);

        $this->get('/forgot-password')->assertSee('Email Password Reset Link')->assertDontSee('contact your admin');
    }
}
