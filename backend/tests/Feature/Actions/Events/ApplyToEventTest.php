<?php

namespace Tests\Feature\Actions\Events;

use App\Actions\Events\ApplyToEvent;
use App\Enums\EventApplicationStatus;
use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprawdza utworzenie zgłoszenia oraz odmowę dla szkicu, zakończonego wydarzenia i ponownego zapisu.
 * Akcja jest wywoływana bez HTTP: te testy nie sprawdzają logowania ani policy.
 * RefreshDatabase izoluje dane kolejnych testów; asercje sprawdzają wynik lub oczekiwany wyjątek.
 */
class ApplyToEventTest extends TestCase
{
    use RefreshDatabase;

    private function createOrganizer(): Organizer
    {
        return Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);
    }

    private function createEvent(EventStatus $status): Event
    {
        $organizer = $this->createOrganizer();

        return Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Test Event',
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(11),
            'location' => 'Test Location',
            'status' => $status,
        ]);
    }

    public function test_user_can_apply_to_published_event(): void
    {
        $user = User::factory()->create();

        $event = $this->createEvent(EventStatus::PUBLISHED);

        $application = app(ApplyToEvent::class)->execute($user, $event);

        $this->assertEquals($user->id, $application->user_id);
        $this->assertEquals($event->id, $application->event_id);
        $this->assertSame(
            EventApplicationStatus::PENDING,
            $application->status
        );

        $this->assertDatabaseHas('event_applications', [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::PENDING->value,
        ]);
    }

    public function test_user_can_apply_to_event_before_application_deadline(): void
    {
        $user = User::factory()->create();

        $event = $this->createEvent(EventStatus::PUBLISHED);
        $event->application_deadline = now()->addDays(5);
        $event->save();

        $application = app(ApplyToEvent::class)->execute($user, $event);

        $this->assertEquals($user->id, $application->user_id);
        $this->assertEquals($event->id,
            $application->event_id);
        $this->assertSame(EventApplicationStatus::PENDING, $application->status);

        $this->assertDatabaseHas('event_applications', [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::PENDING->value,
        ]);
    }

    public function test_user_cannot_apply_to_unpublished_event(): void
    {
        $user = User::factory()->create();

        $event = $this->createEvent(EventStatus::DRAFT);

        $this->expectException(DomainException::class);

        app(ApplyToEvent::class)->execute($user, $event);
    }

    public function test_user_cannot_apply_to_finished_event(): void
    {
        $user = User::factory()->create();

        $event = $this->createEvent(EventStatus::PUBLISHED);
        $event->ends_at = now()->subDay();
        $event->save();

        $this->expectException(DomainException::class);

        app(ApplyToEvent::class)->execute($user, $event);
    }

    public function test_user_cannot_apply_twice_to_same_event(): void
    {
        $user = User::factory()->create();

        $event = $this->createEvent(EventStatus::PUBLISHED);

        $action = app(ApplyToEvent::class);
        $action->execute($user, $event);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('User has already applied to this event.');

        $action->execute($user, $event);

    }

    public function test_user_cannot_apply_to_event_after_application_deadline(): void
    {
        $user = User::factory()->create();

        $event = $this->createEvent(EventStatus::PUBLISHED);
        $event->application_deadline = now()->subDay();
        $event->save();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Application deadline has passed for this event.');

        app(ApplyToEvent::class)->execute($user, $event);
    }
}
