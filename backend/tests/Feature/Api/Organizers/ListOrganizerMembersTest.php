<?php

namespace Tests\Feature\Api\Organizers;

use App\Actions\Organizers\AddOrganizerMember;
use App\Actions\Organizers\CreateOrganizer;
use App\Enums\OrganizerMemberRole;
use App\Enums\OrganizerType;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListOrganizerMembersTest extends TestCase
{
    use RefreshDatabase;

    private function createOrganizer(User $owner): Organizer
    {
        return app(CreateOrganizer::class)->execute(
            $owner,
            'Test Organizer',
            OrganizerType::COMPANY->value,
            'Test description'
        );
    }

    public function test_list_organizer_members(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        app(AddOrganizerMember::class)->execute($member1, $organizer);
        app(AddOrganizerMember::class)->execute($member2, $organizer);

        $response = $this
            ->actingAs($owner)
            ->getJson("/api/organizers/{$organizer->id}/members")
            ->assertOk();

        $response
            ->assertJsonCount(3)
            ->assertJsonFragment([
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
                'role' => OrganizerMemberRole::OWNER->value,
            ])
            ->assertJsonFragment([
                'id' => $member1->id,
                'name' => $member1->name,
                'email' => $member1->email,
                'role' => OrganizerMemberRole::MEMBER->value,
            ])
            ->assertJsonFragment([
                'id' => $member2->id,
                'name' => $member2->name,
                'email' => $member2->email,
                'role' => OrganizerMemberRole::MEMBER->value,
            ]);
    }

    public function test_list_organizer_members_unauthorized(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);
        $member = User::factory()->create();

        app(AddOrganizerMember::class)->execute($member, $organizer);

        $this
            ->actingAs($member)
            ->getJson("/api/organizers/{$organizer->id}/members")
            ->assertForbidden();
    }

    public function test_list_organizer_members_unauthenticated(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);
        $member = User::factory()->create();

        app(AddOrganizerMember::class)->execute($member, $organizer);

        $this
            ->getJson("/api/organizers/{$organizer->id}/members")
            ->assertUnauthorized();
    }

    public function test_list_organizer_members_from_outside_organizer(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);
        $member = User::factory()->create();

        app(AddOrganizerMember::class)->execute($member, $organizer);

        $otherUser = User::factory()->create();

        $this
            ->actingAs($otherUser)
            ->getJson("/api/organizers/{$organizer->id}/members")
            ->assertForbidden();
    }

    public function test_list_organizer_members_from_nonexistent_organizer(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->getJson('/api/organizers/99999/members')
            ->assertNotFound();
    }
}
