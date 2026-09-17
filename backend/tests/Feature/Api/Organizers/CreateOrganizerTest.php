<?php

namespace Tests\Feature\Api\Organizers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\Organizer;
use App\Models\User;

use App\Enums\OrganizerType;
use App\Enums\OrganizerMemberRole;

class CreateOrganizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_organizer(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/organizers', [

            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY->value,
            'description' => 'Test Description',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('organizers', [
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY->value,
            'description' => 'Test Description',
        ]);

        $this->assertDatabaseHas('organizer_user',  [
            'user_id' => $user->id,
            'organizer_id' => Organizer::first()->id,
            'role' => OrganizerMemberRole::OWNER->value,
        ]);

        $organizer = Organizer::firstOrFail();
        $member = $organizer->users()->firstOrFail();
        $this->assertSame(OrganizerMemberRole::OWNER->value,
            $member->pivot->role);
    }

    public function test_guest_cannot_create_organizer(): void
    {
        $response =$this->postJson('/api/organizers', [
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY->value,
            'description' => 'Test Description',
        ]);

        $response->assertUnauthorized();

        $this->assertDatabaseMissing('organizers', [
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY->value,
            'description' => 'Test Description',
        ]);
    }

    public function test_create_organizer_validation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/organizers', [
            'name' => '',
            'type' => 'association',
            'description' => array('Test description'),
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'type', 'description']);
    }

    public function test_create_organizer_without_description(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/organizers', [
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY->value,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('organizers', [
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY->value,
            'description' => null,
        ]);

        $this->assertDatabaseHas('organizer_user', [
            'user_id' => $user->id,
            'organizer_id' => Organizer::first()->id,
            'role' => OrganizerMemberRole::OWNER->value,
        ]);

        $organizer = Organizer::firstOrFail();
        $member = $organizer->users()->firstOrFail();
        $this->assertSame(OrganizerMemberRole::OWNER->value,
            $member->pivot->role);
    }

}
