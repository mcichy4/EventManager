<?php

namespace Tests\Feature\Actions\Events;

use App\Actions\Events\UpdateEvent;
use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprawdza częściową edycję szkicu bez HTTP: pominięte pola, jawne null i wynikowe daty.
 * refresh() odczytuje zapis z bazy. expectException() musi poprzedzać wywołanie akcji.
 * Uprawnienia użytkowników są sprawdzane osobno w testach API.
 */
class UpdateEventTest extends TestCase
{
    use RefreshDatabase;

    private function createOldEventData(EventStatus $status): Event
    {
        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        return Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Old title',
            'description' => 'Test description',
            'status' => $status,
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(11),
            'application_deadline' => now()->addDays(5),
            'participant_limit' => 100,
            'location' => 'Test location',
        ]);
    }

    public function test_start_cannot_move_before_existing_deadline(): void
    {
        $event = $this->createOldEventData(EventStatus::DRAFT);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Application deadline cannot be after the event starts.');

        app(UpdateEvent::class)->handle($event, [
            'starts_at' => $event->application_deadline->copy()->subDay(),
        ]);
    }

    public function test_deadline_can_equal_event_start(): void
    {
        $event = $this->createOldEventData(EventStatus::DRAFT);

        app(UpdateEvent::class)->handle($event, [
            'application_deadline' => $event->starts_at->toDateTimeString(),
        ]);

        $event->refresh();

        $this->assertTrue($event->application_deadline->equalTo($event->starts_at));
    }

    public function test_start_and_deadline_can_be_moved_together(): void
    {
        $event = $this->createOldEventData(EventStatus::DRAFT);
        $newStart = $event->application_deadline->copy()->subDay();
        $newDeadline = $newStart->copy()->subDay();

        app(UpdateEvent::class)->handle($event, [
            'starts_at' => $newStart,
            'application_deadline' => $newDeadline,
        ]);

        $event->refresh();

        $this->assertTrue($event->starts_at->equalTo($newStart));
        $this->assertTrue($event->application_deadline->equalTo($newDeadline));
    }

    public function test_draft_event_can_be_updated(): void
    {
        $event = $this->createOldEventData(EventStatus::DRAFT);

        app(UpdateEvent::class)->handle($event, [
            'title' => 'New title',
        ]);

        $event->refresh();

        $this->assertSame('New title', $event->title);
        $this->assertSame(EventStatus::DRAFT, $event->status);
    }

    public function test_published_event_cannot_be_updated(): void
    {
        $event = $this->createOldEventData(EventStatus::PUBLISHED);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only draft event can be updated');

        app(UpdateEvent::class)->handle($event, [
            'title' => 'New title',
        ]);
    }

    public function test_cancelled_event_cannot_be_updated(): void
    {
        $event = $this->createOldEventData(EventStatus::CANCELLED);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only draft event can be updated');

        app(UpdateEvent::class)->handle($event, [
            'title' => 'New title',
        ]);
    }

    public function test_event_description_can_be_updated_without_changing_title(): void
    {
        $event = $this->createOldEventData(EventStatus::DRAFT);

        app(UpdateEvent::class)->handle($event, [
            'description' => 'New description',
        ]);

        $event->refresh();

        $this->assertSame('New description', $event->description);
        $this->assertSame('Old title', $event->title);

    }

    public function test_event_description_can_be_cleared(): void
    {
        $event = $this->createOldEventData(EventStatus::DRAFT);

        app(UpdateEvent::class)->handle($event, [
            'description' => null,
        ]);

        $event->refresh();

        $this->assertNull($event->description);
    }

    public function test_event_location_can_be_updated_without_changing_title(): void
    {
        $event = $this->createOldEventData(EventStatus::DRAFT);

        app(UpdateEvent::class)->handle($event, [
            'location' => 'New location',
        ]);

        $event->refresh();

        $this->assertSame('New location', $event->location);
        $this->assertSame('Old title', $event->title);

    }

    public function test_event_participant_limit_cannot_be_set_to_zero(): void
    {
        $event = $this->createOldEventData(EventStatus::DRAFT);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Participant limit must be greater than 0');

        app(UpdateEvent::class)->handle($event, [
            'participant_limit' => 0,
        ]);
    }

    public function test_event_participant_limit_cannot_be_set_to_negative_value(): void
    {
        $event = $this->createOldEventData(EventStatus::DRAFT);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Participant limit must be greater than 0');

        app(UpdateEvent::class)->handle($event, [
            'participant_limit' => -10,

        ]);
    }

    public function test_event_participant_limit_can_be_updated(): void
    {
        $event = $this->createOldEventData(EventStatus::DRAFT);

        app(UpdateEvent::class)->handle($event, [
            'participant_limit' => 200,
        ]);

        $event->refresh();

        $this->assertSame(200, $event->participant_limit);
    }

    public function test_event_participant_limit_can_be_cleared(): void
    {
        $event = $this->createOldEventData(EventStatus::DRAFT);

        app(UpdateEvent::class)->handle($event, [
            'participant_limit' => null,
        ]);

        $event->refresh();

        $this->assertNull($event->participant_limit);
    }

    public function test_event_starts_at_cannot_be_set_to_after_ends_at(): void
    {
        $event = $this->createOldEventData(EventStatus::DRAFT);
        $newStartsAt = $event->ends_at->copy()->addDay();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('The starts at must be a date before ends at.');

        app(UpdateEvent::class)->handle($event, [
            'starts_at' => $newStartsAt,
        ]);

        $event->refresh();

    }

    public function test_event_ends_at_cannot_be_set_before_starts_at(): void
    {
        $event = $this->createOldEventData(EventStatus::DRAFT);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('The starts at must be a date before ends at.');

        app(UpdateEvent::class)->handle($event, [
            'ends_at' => now()->addDays(5),
        ]);

        $event->refresh();

    }

    public function test_event_ends_at_cannot_be_set_to_same_as_starts_at(): void
    {
        $event = $this->createOldEventData(EventStatus::DRAFT);
        
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('The starts at must be a date before ends at.');
    
        app(UpdateEvent::class)->handle($event, [
            'ends_at' => $event->starts_at,
        ]);
        }
}
