<?php

namespace Tests\Feature\Api\Events;

use App\Models\Organizer;
use App\Data\Events\CreateEventData;
use App\Models\Event;

use App\Enums\OrganizerType;
use App\Enums\EventStatus;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Carbon\CarbonImmutable;

class ListEventsTest extends TestCase
{
    use RefreshDatabase;

    private function createOrganizer(): Organizer
    {
        return Organizer::forceCreate([
            'name' => 'Organizer Name',
            'type' => OrganizerType::COMPANY,
        ]);
    }

    private function createEvent(EventStatus $status = EventStatus::DRAFT) : Event
    {
        $organizer = $this->createOrganizer();

        $createEventData = new CreateEventData(
            title: 'Event Title',
            description: 'Event Description',
            startsAt: CarbonImmutable::parse(now()->addDays(10)),
            endsAt: CarbonImmutable::parse(now()->addDays(11)),
            applicationDeadline: CarbonImmutable::parse(now()->addDays(5)),
            location: 'Event Location',
            participantLimit: 100
        );

        return Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => $createEventData->title,
            'description' => $createEventData->description,
            'starts_at' => $createEventData->startsAt,
            'ends_at' => $createEventData->endsAt,
            'application_deadline' => $createEventData->applicationDeadline,
            'location' => $createEventData->location,
            'participant_limit' => $createEventData->participantLimit,
            'status' => $status->value,
        ]);
    }

    public function test_guest_can_list_published_events(): void
    {
        $event = $this->createEvent(EventStatus::PUBLISHED);
        $response = $this->getJson("/api/events");

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $event->id,
            'title' => $event->title,
        ]);
    }

    public function test_guest_cannot_list_draft_or_canceled_events(): void
    {
        $event1 = $this->createEvent(EventStatus::DRAFT);
        $event2 = $this->createEvent(EventStatus::CANCELLED);
        $event3 = $this->createEvent(EventStatus::PUBLISHED);

        $response = $this->getJson("/api/events");

        $response->assertOk();

        $response->assertJsonFragment([
            'id' => $event3->id,
            'title' => $event3->title,
        ]);

        $response->assertJsonMissing([
            'id' => $event1->id,
        ]);

        $response->assertJsonMissing([
            'id' =>$event2->id,
        ]);

        }

    }
