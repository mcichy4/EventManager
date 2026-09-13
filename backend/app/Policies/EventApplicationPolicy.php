<?php

namespace App\Policies;

use App\Models\EventApplication;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EventApplicationPolicy
{
    public function cancel(User $user, EventApplication $eventApplication): bool
    {
        return $user->id === $eventApplication->user_id;
    }

    public function accept(User $user, EventApplication $eventApplication): bool
    {
        return $user->organizers()
        ->whereKey($eventApplication->event->organizer_id)
        ->exists();
    }

    public function reject(User $user, EventApplication $eventApplication): bool
    {
        return $user->organizers()
        ->whereKey($eventApplication->event->organizer_id)
        ->exists();
    }
}
