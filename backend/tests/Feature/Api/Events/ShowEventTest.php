<?php

namespace Tests\Feature\Api\Events;

use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowEventTest extends TestCase
{
    use RefreshDatabase;

    private function createOrganizer(): Organizer
    {
        return Organizer::forceCreate([
            'name' => 'Organizer Name',
            'type' => OrganizerType::COMPANY,
        ]);
    }

    private function createEvent(Organizer $organizer, EventStatus $status = EventStatus::DRAFT): Event
    {
        return Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Event Title',
            'description' => 'Event Description',
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(11),
            'application_deadline' => now()->addDays(5),
            'location' => 'Event Location',
            'participant_limit' => 100,
            'status' => $status->value,
        ]);
    }

    public function test_guest_can_show_published_event(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer, EventStatus::PUBLISHED);

        $response = $this->getJson("/api/events/{$event->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $event->id,
            'title' => $event->title,
        ]);
    }

    public function test_guest_cannot_show_draft_event(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer, EventStatus::DRAFT);

        $response = $this->getJson("/api/events/{$event->id}");

        $response->assertNotFound();
    }

    public function test_guest_cannot_show_canceled_event(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer, EventStatus::CANCELLED);

        $response = $this->getJson("/api/events/{$event->id}");

        $response->assertNotFound();
    }

    public function test_guest_cannot_show_nonexistent_event(): void
    {
        $response = $this->getJson('/api/events/999999');

        $response->assertNotFound();
    }
}
