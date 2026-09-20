<?php

namespace App\Actions\Organizers;

use App\Enums\OrganizerMemberRole;
use App\Models\Organizer;
use App\Models\User;

class DeleteOrganizerMember
{
    public function execute(Organizer $organizer, User $member)
    {
        if (! $organizer->users()->whereKey($member->id)->exists()) {
            throw new \DomainException('User is not a member of this organizer.');
        } elseif ($organizer
            ->users()
            ->whereKey($member->id)
            ->wherePivot('role', OrganizerMemberRole::OWNER->value)
            ->exists()
        ) {
            throw new \DomainException('Cannot remove the owner of the organizer.');
        }
        $organizer->users()->detach($member->id);
    }
}
