<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertNoContent();
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    public function test_guest_cannot_access_protected_route(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_take_own_data(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'password',
        ]);

        $this->postJson('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response = $this->getJson('/api/user');

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ]);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertNoContent();

        $response = $this->postJson('/logout');
        $response->assertNoContent();

        $this->assertGuest();

        $response = $this->getJson('/api/user');
        $response->assertUnauthorized();
    }

    public function test_user_cannot_login_with_password_only(): void
    {
        $response = $this->postJson('/login', [
            'password' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    public function test_user_cannot_login_with_email_only(): void
    {
        $response = $this->postJson('/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->assertGuest();
    }
}
