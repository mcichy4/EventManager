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

            public function test_public_event_list_is_paginated(): void
    {
        $organizer = $this->createOrganizer();
        for($i = 0; $i<15; $i++) {
            $this->createEvent(EventStatus::PUBLISHED);
        }

        $response = $this->getJson("/api/events");

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('total', 15);
        $response->assertJsonPath('per_page', 10);
        $response->assertJsonPath('current_page', 1);
        
        $response = $this->getJson("/api/events?page=2");
        $response->assertOk();
        $response->assertJsonCount(5, 'data');
        $response->assertJsonPath('current_page', 2);
    }

    public function test_pagination_total_count_excludes_draft_and_canceled_events(): void
    {
        for($i = 0; $i<2; $i++) {
            $this->createEvent(EventStatus::PUBLISHED);
        }

        $this->createEvent(EventStatus::DRAFT);
        $this->createEvent(EventStatus::CANCELLED);

        $response = $this->getJson("/api/events");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('total', 2);
    }
    }
