<?php

namespace App\Actions\Events;

use App\Data\Events\CreateEventData;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organizer;

final class CreateEvent
{
    public function execute(
        Organizer $organizer,
        CreateEventData $data
    ): Event {
        if ($data->endsAt < $data->startsAt) {
            throw new \DomainException('Event cannot end before it starts.');
        }

        if ($data->participantLimit !== null && $data->participantLimit <= 0) {
            throw new \DomainException('Participant limit must be greater than 0.');
        }

        if ($data->applicationDeadline !== null && $data->applicationDeadline > $data->startsAt) {
            throw new \DomainException('Application deadline cannot be after the event starts.');
        }

        return Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => $data->title,
            'description' => $data->description,
            'starts_at' => $data->startsAt,
            'ends_at' => $data->endsAt,
            'application_deadline' => $data->applicationDeadline,
            'location' => $data->location,
            'participant_limit' => $data->participantLimit,
            'status' => EventStatus::DRAFT,
        ]);
    }
}
