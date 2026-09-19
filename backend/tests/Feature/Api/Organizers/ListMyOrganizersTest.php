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

class ListMyOrganizersTest extends TestCase
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

    public function test_list_my_organizers(): void
    {
        $user = User::factory()->create();
        $otherOwner = User::factory()->create();

        $this->actingAs($user, 'sanctum');

        $organizer1 = $this->createOrganizer($user);
        $organizer2 = $this->createOrganizer($otherOwner);
        app(AddOrganizerMember::class)->execute($user, $organizer2);

        $this->getJson('/api/organizers/my')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $organizer1->id,
                'name' => $organizer1->name,
                'type' => OrganizerType::COMPANY->value,
                'description' => $organizer1->description,
                'role' => OrganizerMemberRole::OWNER->value,
            ])
            ->assertJsonFragment([
                'id' => $organizer2->id,
                'name' => $organizer2->name,
                'type' => OrganizerType::COMPANY->value,
                'description' => $organizer2->description,
                'role' => OrganizerMemberRole::MEMBER->value,
            ]);
    }

    public function test_list_my_organizers_empty(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->getJson('/api/organizers/my')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_list_my_organizers_unauthenticated(): void
    {
        $this->getJson('/api/organizers/my')
            ->assertUnauthorized();
    }
}
