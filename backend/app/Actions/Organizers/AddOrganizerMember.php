<?php

namespace App\Actions\Organizers;

use App\Enums\OrganizerMemberRole;
use App\Models\Organizer;
use App\Models\User;

class AddOrganizerMember
{
    public function execute(User $user, Organizer $organizer): void
    {
        if ($organizer->users()->whereKey($user->id)->exists()) {
            throw new \DomainException('User is already a member of this organizer.');
        }

        $organizer->users()->attach($user, [
            'role' => OrganizerMemberRole::MEMBER->value,
        ]);
    }
}
