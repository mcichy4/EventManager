<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

/**
 * Odpowiada na pytanie, kto może zarządzać wydarzeniem, a nie czy pozwala na to jego status.
 * Metody zwracające false są niewdrożonymi regułami, które odmawiają dostępu przy ich wywołaniu.
 */
class EventPolicy
{
    /**
     * Reguła listowania jeszcze niewdrożona: wywołanie tej metody odmówi dostępu.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Reguła podglądu jeszcze niewdrożona.
     */
    public function view(User $user, Event $event): bool
    {
        return false;
    }

    /**
     * Reguła jeszcze niewdrożona tutaj; obecny endpoint sprawdza dostęp w CreateEventRequest.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Pozwala edytować wydarzenie członkowi jego organizatora.
     */
    public function update(User $user, Event $event): bool
    {
        return $user->organizers()
            ->whereKey($event->organizer_id)
            ->exists();
    }

    /**
     * Reguła usuwania jeszcze niewdrożona.
     */
    public function delete(User $user, Event $event): bool
    {
        return false;
    }

    /**
     * Reguła przywracania jeszcze niewdrożona; model nie korzysta obecnie z SoftDeletes.
     */
    public function restore(User $user, Event $event): bool
    {
        return false;
    }

    /**
     * Reguła trwałego usuwania jeszcze niewdrożona.
     */
    public function forceDelete(User $user, Event $event): bool
    {
        return false;
    }

    // exists sprawdza członkostwo w bazie bez pobierania całej kolekcji organizatorów.
    public function publish(User $user, Event $event): bool
    {
        return $user->organizers()
            ->whereKey($event->organizer_id)
            ->exists();
    }
}
