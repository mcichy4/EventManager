<?php

namespace Tests\Feature\Actions\Events;

use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\Organizer;
use App\Actions\Events\DeleteEvent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DeleteEventTest extends TestCase
{
    use RefreshDatabase;

    private function createEvent(EventStatus $status): Event
    {
        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY
        ]);

        return Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Event Title',
            'description' => 'Event description',
            'status' => $status,
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(11),
            'application_deadline' => now()->addDays(6),
            'participant_limit' => 100,
            'location' => 'Test Location',
        ]);
    }

    public function test_draft_event_can_be_deleted(): void
    {
        $event = $this->createEvent(EventStatus::DRAFT);

        app(DeleteEvent::class)->handle($event);

        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);
    }

    public function test_published_event_cannot_be_deleted(): void
    {
        $event = $this->createEvent(EventStatus::PUBLISHED);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only draft event can be deleted');

        app(DeleteEvent::class)->handle($event);
    }

    public function test_cancelled_event_cannot_be_deleted(): void
    {
        $event = $this->createEvent(EventStatus::CANCELLED);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only draft event can be deleted');

        app(DeleteEvent::class)->handle($event);
    }
}
