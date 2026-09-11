<?php

namespace Tests\Feature\Actions\Events;

use App\Actions\Events\CancelEventApplication;
use App\Enums\EventApplicationStatus;
use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\EventApplication;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprawdza wycofanie zgłoszeń pending i accepted oraz odmowę dla rejected i cancelled.
 * Akcja jest wywoływana bez HTTP: te testy nie sprawdzają logowania ani policy.
 * RefreshDatabase izoluje dane kolejnych testów; asercje sprawdzają wynik lub oczekiwany wyjątek.
 */
class CancelEventApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_application_can_be_cancelled(): void
    {
        $eventApplication = $this->createEventApplication(
            EventApplicationStatus::PENDING
        );
        $result = app(CancelEventApplication::class)->execute($eventApplication);
        $this->assertSame(EventApplicationStatus::CANCELLED, $result->status);
        $this->assertDatabaseHas('event_applications', [
            'id' => $result->id,
            'status' => EventApplicationStatus::CANCELLED->value,
        ]);
    }

    public function test_accepted_application_can_be_cancelled(): void
    {
        $eventApplication = $this->createEventApplication(
            EventApplicationStatus::ACCEPTED
        );

        $result = app(CancelEventApplication::class)->execute($eventApplication);
        $this->assertSame(EventApplicationStatus::CANCELLED, $result->status);
        $this->assertDatabaseHas('event_applications', [
            'id' => $result->id,
            'status' => EventApplicationStatus::CANCELLED->value,
        ]);
    }

    public function test_rejected_application_cannot_be_cancelled(): void
    {
        $evenApplication = $this->createEventApplication(
            EventApplicationStatus::REJECTED
        );
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only pending or accepted applications can be cancelled.');
        app(CancelEventApplication::class)->execute($evenApplication);
    }

    public function test_cancelled_application_cannot_be_cancelled_again(): void
    {
        $eventApplication = $this->createEventApplication(
            EventApplicationStatus::CANCELLED
        );
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only pending or accepted applications can be cancelled.');
        app(CancelEventApplication::class)->execute($eventApplication);
    }

    private function createEventApplication(EventApplicationStatus $status = EventApplicationStatus::PENDING): EventApplication
    {
        $user = User::factory()->create();
        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        $event = Event::forceCreate([
            'title' => 'Test Event',
            'organizer_id' => $organizer->id,
            'description' => 'This is a test event',
            'participant_limit' => null,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
            'location' => 'Test Location',
            'status' => EventStatus::PUBLISHED,
        ]);

        return EventApplication::forceCreate([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => $status,
        ]);

    }
}
