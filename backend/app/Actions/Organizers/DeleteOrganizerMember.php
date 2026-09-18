<?php

namespace App\Actions\Organizers;

use App\Models\Organizer;
use App\Models\User;
use App\Enums\OrganizerMemberRole;
class DeleteOrganizerMember
{
    public function execute(Organizer $organizer, User $member)
    {
        if(!$organizer->users()->whereKey($member->id)->exists()) {
            throw new \DomainException('User is not a member of this organizer.');
        } else if($organizer
                ->users()
                ->whereKey($member->id)
                ->wherePivot('role', OrganizerMemberRole::OWNER->value)
                ->exists()
                ){
            throw new \DomainException('Cannot remove the owner of the organizer.');
        }
        $organizer->users()->detach($member->id);
    }
}