<?php

namespace App\Actions\Events;

use App\Enums\EventApplicationStatus;
use App\Models\EventApplication;
use DomainException;
use Illuminate\Support\Facades\DB;

class AcceptEventApplication
{
    public function execute(EventApplication $eventApplication): EventApplication
    {

        return DB::transaction(function () use ($eventApplication) {
            $eventApplication = EventApplication::query()
                ->lockForUpdate()
                ->find($eventApplication->id);

            if ($eventApplication->status !== EventApplicationStatus::PENDING) {
                throw new DomainException('Only pending applications can be accepted.');
            }

            $event = $eventApplication->event
                ->lockForUpdate()
                ->firstOrFail();

            if ($event->isFinished()) {
                throw new DomainException('Cannot accept applications for finished events.');
            }

            if ($event->participant_limit !== null) {
                $acceptedApplicationsCount = EventApplication::where('event_id', $event->id)
                    ->where('status', EventApplicationStatus::ACCEPTED)
                    ->count();

                if ($acceptedApplicationsCount >= $event->participant_limit) {
                    throw new DomainException('Cannot accept application: participant limit reached.');
                }
            }
            $eventApplication->status = EventApplicationStatus::ACCEPTED;
            $eventApplication->save();

            return $eventApplication;
        });

    }
}
