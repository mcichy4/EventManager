<?php

namespace Tests\Feature\Policies;

use App\Actions\Organizers\CreateOrganizer;
use App\Enums\OrganizerMemberRole;
use App\Enums\OrganizerType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_member(): void
    {
        $owner = User::factory()->create();
        $organizer = (new CreateOrganizer)->execute(
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

        $this->assertTrue($owner->can('manageMember', $organizer));
    }

    public function test_member_cannot_manage_member(): void
    {
        $owner = User::factory()->create();
        $organizer = (new CreateOrganizer)->execute(
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

        $this->assertFalse($member->can('manageMember', $organizer));
    }
}
