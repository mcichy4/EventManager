<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use \DomainException;

class UpdateEvent
{
    public function handle(Event $event, array $data): Event
    {

        if($event->status !== EventStatus::DRAFT){
            throw new \DomainException('Only draft event can be updated ');
        }

        $event->title = $data['title'];
        $event->save();

        return $event;
    }
}