<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'email' => 'jan.kowalski@example.com',
            'password' => bcrypt('password'),
            'name' => 'Jan Kowalski',
        ]);
    }

    public function test_user_login(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/login', [
            'email' => 'jan.kowalski@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ])
            ->assertJsonMissingPath('user.password');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_login_invalid_password(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/login', [
            'email' => 'jan.kowalski@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);

        $this->assertGuest();
    }

    public function test_user_login_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'jan.kowalski@example.com',
            'password' => 'password',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);

        $this->assertGuest();
    }

    public function test_user_login_with_missing_fields(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);

        $this->assertGuest();
    }
}
