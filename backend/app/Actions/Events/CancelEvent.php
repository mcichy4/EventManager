<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use DomainException;

/**
 * Anuluje opublikowane wydarzenie, zachowując jego rekord w bazie.
 */
class CancelEvent
{
    public function handle(Event $event): void
    {
        if ($event->status !== EventStatus::PUBLISHED) {
            throw new DomainException('Only published events can be cancelled.');
        }
        $event->status = EventStatus::CANCELLED;
        $event->save();
    }
}
