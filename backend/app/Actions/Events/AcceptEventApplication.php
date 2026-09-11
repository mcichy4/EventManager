<?php

namespace App\Actions\Events;

use App\Enums\EventApplicationStatus;
use App\Models\EventApplication;
use DomainException;
use Illuminate\Support\Facades\DB;
use App\Enums\EventStatus;


/**
 * Akceptuje oczekujące zgłoszenie po sprawdzeniu zakończenia wydarzenia i liczby zaakceptowanych uczestników.
 */
class AcceptEventApplication
{
    public function execute(EventApplication $eventApplication): EventApplication
    {
        // Transakcja wycofa zapis w razie wyjątku. Ponownie pobieramy zgłoszenie
        // z blokadą rekordu, aby nie podejmować decyzji na podstawie starego statusu.
        return DB::transaction(function () use ($eventApplication) {
            $eventApplication = EventApplication::query()
                ->lockForUpdate()
                ->find($eventApplication->id);

            if ($eventApplication->status !== EventApplicationStatus::PENDING) {
                throw new DomainException('Only pending applications can be accepted.');
            }

            // Wspólna blokada wydarzenia ma chronić limit przy równoległej akceptacji zgłoszeń.
            // Uwaga: obecne wywołanie na właściwości event wymaga poprawki na zapytanie
            // relacji event(), aby ograniczyć pobranie do wydarzenia tego zgłoszenia.
            $event = $eventApplication->event()
                ->lockForUpdate()
                ->firstOrFail();


            if($event->status !== EventStatus::PUBLISHED) {
                throw new DomainException('Cannot accept applications for events that are not published.');
            }

            if ($event->isFinished()) {
                throw new DomainException('Cannot accept applications for finished events.');
            }

            // Miejsca zajmują zaakceptowane zgłoszenia; null oznacza brak limitu.
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
