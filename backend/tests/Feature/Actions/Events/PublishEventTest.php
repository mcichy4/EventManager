<?php

namespace Tests\Feature\Actions\Events;

use App\Actions\Events\PublishEvent;
use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\Organizer;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprawdza publikację szkicu i odrzucenie ponownej publikacji lub publikacji anulowanego wydarzenia.
 * Akcja jest wywoływana bez HTTP: te testy nie sprawdzają logowania ani policy.
 * RefreshDatabase izoluje dane kolejnych testów; asercje sprawdzają wynik lub oczekiwany wyjątek.
 */
class PublishEventTest extends TestCase
{
    use RefreshDatabase;

    private function createEvent(EventStatus $status): Event
    {
        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        return Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Test Event',
            'description' => 'Test description',
            'status' => $status,
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(11),
            'application_deadline' => now()->addDays(5),
            'participant_limit' => 100,
            'location' => 'Test location',
        ]);
    }

    public function test_draft_event_can_be_published(): void
    {
        $event = $this->createEvent(EventStatus::DRAFT);

        $publishedEvent = app(PublishEvent::class)->execute($event);

        $this->assertSame(EventStatus::PUBLISHED, $publishedEvent->status);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => EventStatus::PUBLISHED->value,
        ]);
    }

    public function test_published_event_cannot_be_published_again(): void
    {
        $event = $this->createEvent(EventStatus::PUBLISHED);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Only draft events can be published');

        app(PublishEvent::class)->execute($event);
    }

    public function test_cancelled_event_cannot_be_published(): void
    {
        $event = $this->createEvent(EventStatus::CANCELLED);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Only draft events can be published');

        app(PublishEvent::class)->execute($event);
    }
}
