<?php

namespace Tests\Feature\Actions\Events;

use App\Actions\Events\CancelEvent;
use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprawdza przejście published → cancelled oraz odrzucenie pozostałych statusów.
 * Akcja jest wywoływana bez HTTP: te testy nie sprawdzają logowania ani policy.
 * RefreshDatabase izoluje dane kolejnych testów; asercje sprawdzają wynik lub oczekiwany wyjątek.
 */
class CancelEventTest extends TestCase
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

    public function test_published_event_can_be_cancelled(): void
    {
        $event = $this->createEvent(EventStatus::PUBLISHED);

        app(CancelEvent::class)->handle($event);

        $this->assertSame(EventStatus::CANCELLED, $event->fresh()->status);
    }

    public function test_draft_event_cannot_be_cancelled(): void
    {
        $event = $this->createEvent(EventStatus::DRAFT);

        $this->expectException(\DomainException::class);

        app(CancelEvent::class)->handle($event);
    }

    public function test_cancelled_event_cannot_be_cancelled_again(): void
    {
        $event = $this->createEvent(EventStatus::CANCELLED);
        $this->expectException(\DomainException::class);

        app(CancelEvent::class)->handle($event);
    }
}
