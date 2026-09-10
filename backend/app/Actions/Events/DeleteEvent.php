<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use DomainException;

class DeleteEvent
{
    public function handle(Event $event) {
        if($event->status !== EventStatus::DRAFT) {
            throw new DomainException('Only draft event can be deleted');
        }
        $event->delete();
        }
}