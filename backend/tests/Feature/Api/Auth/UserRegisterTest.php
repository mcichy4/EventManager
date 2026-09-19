<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class UserRegisterTest extends TestCase
{
    use RefreshDatabase;

    private function registerUser(): TestResponse
    {
        return $this->postJson('/api/register', [
            'name' => 'Jan Kowalski',
            'email' => 'jan.kowalski@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
    }

    public function test_user_register(): void
    {
        $response = $this->registerUser();

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Jan Kowalski',
            'email' => 'jan.kowalski@example.com',
        ]);

        $this->assertAuthenticated();
    }

    public function test_user_cannot_register_with_existing_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'jan.kowalski@example.com',
        ]);

        $response = $this->registerUser();

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_cannot_register_with_invalid_password_confirmation(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Jan Kowalski',
            'email' => 'jan.kowalski@example.com',
            'password' => 'password',
            'password_confirmation' => 'wrong_password',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseMissing('users', [
            'email' => 'jan.kowalski@example.com',
            'name' => 'Jan Kowalski',
        ]);
    }

    public function test_user_created_password_isnt_shown_in_response(): void
    {
        $response = $this->registerUser();

        $response
            ->assertCreated()
            ->assertJsonMissingPath('user.password');
    }
}
