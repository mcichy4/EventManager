<?php

namespace Tests\Feature\Policies;

use App\Actions\Organizers\CreateOrganizer;
use App\Enums\OrganizerMemberRole;
use App\Enums\OrganizerType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddOrganizerMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_organizer_member(): void
    {
        $owner = User::factory()->create();
        $organizer = app(CreateOrganizer::class)->execute(
            $owner,
            'Test Organizer',
            OrganizerType::COMPANY->value,
            'Test Description'
        );

        $member = User::factory()->create();

        $this->actingAs($owner)
            ->postJson("/api/organizers/{$organizer->id}/members", [
                'user_id' => $member->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('organizer_user', [
            'organizer_id' => $organizer->id,
            'user_id' => $member->id,
            'role' => OrganizerMemberRole::MEMBER->value,
        ]);
    }

    public function test_non_owner_cannot_add_organizer_member(): void
    {
        $owner = User::factory()->create();
        $organizer = app(CreateOrganizer::class)->execute(
            $owner,
            'Test Organizer',
            OrganizerType::COMPANY->value,
            'Test Description'
        );

        $nonOwner = User::factory()->create();
        $organizer->users()->attach(
            $nonOwner,
            [
                'role' => OrganizerMemberRole::MEMBER->value,
            ]
        );

        $member = User::factory()->create();

        $this->actingAs($nonOwner)
            ->postJson("/api/organizers/{$organizer->id}/members", [
                'user_id' => $member->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('organizer_user', [
            'organizer_id' => $organizer->id,
            'user_id' => $member->id,
            'role' => OrganizerMemberRole::MEMBER->value,
        ]);
    }

    public function test_owner_can_add_organizer_member_by_email(): void
    {
        $owner = User::factory()->create();
        $organizer = app(CreateOrganizer::class)->execute(
            $owner,
            'Test Organizer',
            OrganizerType::COMPANY->value,
            'Test Description'
        );
        $member = User::factory()->create();

        $this->actingAs($owner)
            ->postJson("/api/organizers/{$organizer->id}/members", [
                'email' => $member->email,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('organizer_user', [
            'organizer_id' => $organizer->id,
            'user_id' => $member->id,
            'role' => OrganizerMemberRole::MEMBER->value,
        ]);
    }

    public function test_guest_cannot_add_organizer_member(): void
    {
        $owner = User::factory()->create();
        $organizer = app(CreateOrganizer::class)->execute(
            $owner,
            'Test Organizer',
            OrganizerType::COMPANY->value,
            'Test Description'
        );

        $member = User::factory()->create();

        $this->postJson("/api/organizers/{$organizer->id}/members", [
            'user_id' => $member->id,
        ])
            ->assertUnauthorized();

        $this->assertDatabaseMissing('organizer_user', [
            'organizer_id' => $organizer->id,
            'user_id' => $member->id,
            'role' => OrganizerMemberRole::MEMBER->value,
        ]);
    }

    public function test_add_member_with_invalid_user_id(): void
    {
        $owner = User::factory()->create();
        $organizer = app(CreateOrganizer::class)->execute(
            $owner,
            'Test Organizer',
            OrganizerType::COMPANY->value,
            'Test Description'
        );

        $invalidUserId = 9999;

        $this->actingAs($owner)
            ->postJson("/api/organizers/{$organizer->id}/members", [
                'user_id' => $invalidUserId,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('organizer_user', [
            'organizer_id' => $organizer->id,
            'user_id' => $invalidUserId,
            'role' => OrganizerMemberRole::MEMBER->value,
        ]);
    }

    public function test_add_member_which_is_already_a_member(): void
    {
        $owner = User::factory()->create();
        $organizer = app(CreateOrganizer::class)->execute(
            $owner,
            'Test Organizer',
            OrganizerType::COMPANY->value,
            'Test Description'
        );

        $member = User::factory()->create();
        $organizer->users()->attach(
            $member,
            [
                'role' => OrganizerMemberRole::MEMBER->value,
            ]
        );

        $this->actingAs($owner)
            ->postJson("/api/organizers/{$organizer->id}/members", [
                'user_id' => $member->id,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('organizer_user', 2);
    }
}
