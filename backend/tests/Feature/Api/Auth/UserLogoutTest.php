<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('current-token');
        $otherToken = $user->createToken('other-token');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$currentToken->plainTextToken)
            ->postJson('/api/logout');

        $response->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $currentToken->accessToken->id,
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $otherToken->accessToken->id,
        ]);
    }

    public function test_guest_cannot_logout(): void
    {
        $this->postJson('/api/logout')
            ->assertUnauthorized();
    }
}
