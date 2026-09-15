<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use DomainException;

/**
 * Zmienia szkic w opublikowane wydarzenie. Uprawnienia użytkownika sprawdza osobno warstwa HTTP.
 */
class PublishEvent
{
    public function execute(Event $event): Event
    {
        if ($event->status !== EventStatus::DRAFT) {
            throw new DomainException('Only draft events can be published');
        }

        $event->status = EventStatus::PUBLISHED;

        $event->save();

        return $event;
    }
}
