<?php

namespace Tests\Feature\Api\Organizers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
use App\Models\Organizer;
use App\Actions\Organizers\CreateOrganizer;
use App\Actions\Organizers\AddOrganizerMember;
use App\Enums\OrganizerType;
use App\Enums\OrganizerMemberRole;

class DeleteOrganizerMembersTest extends TestCase
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

    public function test_delete_organizer_member(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);
        $member = User::factory()->create();

        app(AddOrganizerMember::class)->execute($member, $organizer);

        $response = $this
            ->actingAs($owner)
            ->deleteJson("/api/organizers/{$organizer->id}/members/{$member->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('organizer_user', [
            'organizer_id' => $organizer->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_regular_member_cannot_delete_organizer_member(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);
        $regularMember = User::factory()->create();
        $memberToDelete = User::factory()->create();
        app(AddOrganizerMember::class)->execute($regularMember, $organizer);
        app(AddOrganizerMember::class)->execute($memberToDelete, $organizer);

        $response = $this
            ->actingAs($regularMember)
            ->deleteJson("/api/organizers/{$organizer->id}/members/{$memberToDelete->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('organizer_user', [
            'organizer_id' => $organizer->id,
            'user_id' => $memberToDelete->id,
        ]);
    }

    public function test_other_member_cannot_delete_organizer_member(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        app(AddOrganizerMember::class)->execute($member1, $organizer);

        $response = $this
            ->actingAs($member2)
            ->deleteJson("/api/organizers/{$organizer->id}/members/{$member1->id}")
            ->assertForbidden();
        
        $this->assertDatabaseHas('organizer_user', [
            'organizer_id' => $organizer->id,
            'user_id' => $member1->id,
        ]);
    }

    public function test_guest_cannot_delete_organizer_member(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);
        $member = User::factory()->create();
        app(AddOrganizerMember::class)->execute($member, $organizer);

        $response = $this
            ->deleteJson("/api/organizers/{$organizer->id}/members/{$member->id}")
            ->assertUnauthorized();

        $this->assertDatabaseHas('organizer_user', [
            'organizer_id' => $organizer->id,
            'user_id' => $member->id
        ]);
    }

    public function test_delete_nonexistent_organizer_member(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);
        $nonexistentMemberId = 9999999;

        $reponse = $this
            ->actingAs($owner)
            ->deleteJson("/api/organizers/{$organizer->id}/members/{$nonexistentMemberId}")
            ->assertNotFound();
    }

    public function test_delete_member_who_is_not_a_member_of_the_organizer(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);
        $nonMember = User::factory()->create();

        $response = $this
            ->actingAs($owner)
            ->deleteJson("/api/organizers/{$organizer->id}/members/{$nonMember->id}")
            ->assertUnprocessable();
    }

    public function test_owner_cannot_delete_themselves(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);

        $response = $this
            ->actingAs($owner)
            ->deleteJson("/api/organizers/{$organizer->id}/members/{$owner->id}")
            ->assertUnprocessable();

        $this->assertDatabaseHas('organizer_user', [
            'organizer_id' => $organizer->id,
            'user_id' => $owner->id
        ]);
    }
}
