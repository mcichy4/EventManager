<?php

namespace App\Actions\Events;

use App\Enums\EventApplicationStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventApplication;
use App\Models\User;
use DomainException;

/**
 * Tworzy oczekujące zgłoszenie na opublikowane, niezakończone wydarzenie, bez ponownego zapisu tego samego użytkownika.
 */
class ApplyToEvent
{
    public function execute(User $user, Event $event): EventApplication
    {
        if ($event->status !== EventStatus::PUBLISHED) {
            throw new DomainException('Cannot apply to an event that is not published.');
        }

        if ($event->isFinished()) {
            throw new DomainException('Cannot apply to an event that has already finished.');
        }

        if ($event->applications()->where('user_id', $user->id)->exists()) {
            throw new DomainException('User has already applied to this event.');
        }

        // return EventApplication::create([
        //     'user_id' => $user->id,
        //     'event_id' => $event->id,
        //     'status' => EventApplicationStatus::PENDING,
        // ]);

        // Tworzenie przez relację automatycznie przypisuje event_id tego wydarzenia.
        // Ta akcja nie sprawdza jeszcze application_deadline.
        return $event->applications()->create([
            'user_id' => $user->id,
            'status' => EventApplicationStatus::PENDING,
        ]);
    }
}
