<?php

namespace Tests\Feature\Policies;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
use App\Models\Organizer;
use App\Actions\Organizers\CreateOrganizer;
use App\Enums\OrganizerType;
use App\Enums\OrganizerMemberRole;

class OrganizerPolicyTest extends TestCase
{

    use RefreshDatabase;

    public function test_owner_can_manage_member(): void
    {
        $owner = User::factory()->create();
        $organizer = (new CreateOrganizer())->execute(
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
        $organizer = (new CreateOrganizer())->execute(
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
