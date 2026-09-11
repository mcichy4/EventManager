<?php

namespace App\Actions\Events;

use App\Enums\EventApplicationStatus;
use App\Models\EventApplication;
use DomainException;

/**
 * Pozwala wycofać zgłoszenie oczekujące lub zaakceptowane, bez usuwania jego historii z bazy.
 */
class CancelEventApplication
{
    public function execute(EventApplication $eventApplication): EventApplication
    {
        if (! in_array($eventApplication->status, [
            EventApplicationStatus::PENDING,
            EventApplicationStatus::ACCEPTED,
        ], true)) {
            throw new DomainException('Only pending or accepted applications can be cancelled.');
        }

        $eventApplication->status = EventApplicationStatus::CANCELLED;
        $eventApplication->save();

        return $eventApplication;
    }
}
