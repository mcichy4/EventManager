<?php

namespace Tests\Feature\Api\Events;

use App\Data\Events\CreateEventData;
use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\Organizer;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

    private function createEvent(EventStatus $status = EventStatus::DRAFT): Event
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
        $response = $this->getJson('/api/events');

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

        $response = $this->getJson('/api/events');

        $response->assertOk();

        $response->assertJsonFragment([
            'id' => $event3->id,
            'title' => $event3->title,
        ]);

        $response->assertJsonMissing([
            'id' => $event1->id,
        ]);

        $response->assertJsonMissing([
            'id' => $event2->id,
        ]);

    }

    public function test_public_event_list_is_paginated(): void
    {
        $organizer = $this->createOrganizer();
        for ($i = 0; $i < 15; $i++) {
            $this->createEvent(EventStatus::PUBLISHED);
        }

        $response = $this->getJson('/api/events');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('total', 15);
        $response->assertJsonPath('per_page', 10);
        $response->assertJsonPath('current_page', 1);

        $response = $this->getJson('/api/events?page=2');
        $response->assertOk();
        $response->assertJsonCount(5, 'data');
        $response->assertJsonPath('current_page', 2);
    }

    public function test_pagination_total_count_excludes_draft_and_canceled_events(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $this->createEvent(EventStatus::PUBLISHED);
        }

        $this->createEvent(EventStatus::DRAFT);
        $this->createEvent(EventStatus::CANCELLED);

        $response = $this->getJson('/api/events');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('total', 2);
    }

    public function test_guest_can_search_event_by_title(): void
    {
        $organizer = $this->createOrganizer();
        $event1 = Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Laravel Conference',
            'description' => 'Event Description',
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(11),
            'application_deadline' => now()->addDays(5),
            'location' => 'Event Location',
            'participant_limit' => 100,
            'status' => EventStatus::PUBLISHED->value,
        ]);

        $event2 = Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Symfony Conference',
            'description' => 'Event Description',
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(11),
            'application_deadline' => now()->addDays(5),
            'location' => 'Event Location',
            'participant_limit' => 100,
            'status' => EventStatus::PUBLISHED->value,
        ]);

        $response = $this->getJson('/api/events?search=Laravel');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonMissing([
            'id' => $event2->id,
        ]);
        $response->assertJsonFragment([
            'id' => $event1->id,
        ]);
        $response->assertJsonPath('total', 1);
    }

    public function test_search_only_returns_published_events(): void
    {
        $event1 = $this->createEvent(EventStatus::DRAFT);
        $event2 = $this->createEvent(EventStatus::CANCELLED);
        $event3 = $this->createEvent(EventStatus::PUBLISHED);

        $response = $this->getJson('/api/events?search=Event');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('total', 1);
        $response->assertJsonFragment([
            'id' => $event3->id,
            'status' => EventStatus::PUBLISHED->value,
        ]);

        $response->assertJsonMissing([
            'id' => $event1->id,
        ]);

        $response->assertJsonMissing([
            'id' => $event2->id,
        ]);
    }

    public function test_next_page_link_preserves_search(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->createEvent(EventStatus::PUBLISHED);
        }

        $specialEvent = Event::forceCreate([
            'organizer_id' => $this->createOrganizer()->id,
            'title' => 'Special Event',
            'description' => 'Event Description',
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(11),
            'application_deadline' => now()->addDays(4),
            'location' => 'Event Location',
            'participant_limit' => 100,
            'status' => EventStatus::PUBLISHED,
        ]);

        $response = $this->getJson('/api/events?search=Title');

        $response->assertOk();
        $response->assertJsonPath('total', 15);
        $response->assertJsonCount(10, 'data');
        $nextResponse = $response->json('next_page_url');
        $this->assertNotNull($nextResponse);
        $secondResponse = $this->getJson($nextResponse);
        $secondResponse->assertOk();
        $secondResponse->assertJsonCount(5, 'data');
        $secondResponse->assertJsonPath('total', 15);
        $secondResponse->assertJsonPath('current_page', 2);
        $secondResponse->assertJsonMissing(['id' => $specialEvent->id]);
    }

    public function test_search_returns_empty_list_when_nothing_matches(): void
    {
        $this->createEvent(EventStatus::PUBLISHED);

        $response = $this->getJson('/api/events?search=NieistniejacyTytul');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('total', 0);
    }
}
