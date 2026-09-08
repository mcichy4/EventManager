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
        // Keep the status check, capacity check and update atomic. Re-fetching the
        // application under a row lock also prevents decisions based on stale state.
        return DB::transaction(function () use ($eventApplication) {
            $eventApplication = EventApplication::query()
                ->lockForUpdate()
                ->find($eventApplication->id);

            if ($eventApplication->status !== EventApplicationStatus::PENDING) {
                throw new DomainException('Only pending applications can be accepted.');
            }

            // The event is the shared lock for all of its applications. Locking only
            // one application would let concurrent acceptances observe the same free
            // slot and both exceed participant_limit.
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
