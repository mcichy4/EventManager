<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Organizer;
use App\Enums\OrganizerMemberRole;

class OrganizerPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function manageMember(User $user, Organizer $organizer): bool
    {
        return $organizer
            ->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', OrganizerMemberRole::OWNER->value)
            ->exists();
    }
}
