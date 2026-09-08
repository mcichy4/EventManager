<?php

namespace App\Actions\Events;

use App\Enums\EventApplicationStatus;
use App\Models\EventApplication;

// use DomainException;

class RejectEventApplication
{
    public function execute(EventApplication $eventApplication): EventApplication
    {
        if ($eventApplication->status !== EventApplicationStatus::PENDING) {
            throw new \DomainException('Only pending applications can be rejected.');
        }

        $eventApplication->status = EventApplicationStatus::REJECTED;
        $eventApplication->save();

        return $eventApplication;
    }
}
