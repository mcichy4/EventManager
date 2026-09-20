<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'If an account exists, a password reset link has been sent.',
            ]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_receives_same_response_without_notification(): void
    {
        Notification::fake();

        $this->postJson('/api/forgot-password', [
            'email' => 'unknown@example.com',
        ])
            ->assertOk()
            ->assertJson([
                'message' => 'If an account exists, a password reset link has been sent.',
            ]);

        Notification::assertNothingSent();
    }

    public function test_password_reset_link_points_to_frontend(): void
    {
        Notification::fake();
        config(['services.frontend.url' => 'http://frontend.test']);
        $user = User::factory()->create();

        $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, 'http://frontend.test/reset-password?')
                && str_contains($url, 'email='.urlencode($user->email));
        });
    }
}
