<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_reset_password(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'password' => 'old-password',
        ]);
        $user->createToken('existing-token');

        $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ])->assertOk();

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertOk()
            ->assertJson([
                'message' => 'Password has been reset successfully.',
            ]);

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_cannot_reset_password_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password',
        ]);

        $this->postJson('/api/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'Invalid or expired password reset token.',
            ]);

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
