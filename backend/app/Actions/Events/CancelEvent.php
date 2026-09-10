<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use \DomainException;


class CancelEvent
{
    public function handle(Event $event): void
    {
        if($event->status !== EventStatus::PUBLISHED) {
            throw new \DomainException('Only published events can be cancelled.');
            
            }
            $event->status = EventStatus::CANCELLED;
            $event->save();
    }
}
