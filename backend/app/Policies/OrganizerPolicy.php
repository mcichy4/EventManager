<?php

namespace App\Policies;

use App\Enums\OrganizerMemberRole;
use App\Models\Organizer;
use App\Models\User;

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

    public function viewEvents(User $user, Organizer $organizer): bool
    {
        return $organizer
            ->users()
            ->whereKey($user->id)
            ->exists();
    }
}
