<?php

namespace Tests\Feature\Actions\Events;

use App\Actions\Events\CreateEvent;
use App\Data\Events\CreateEventData;
use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_can_be_created(): void
    {
        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        $data = new CreateEventData(
            title: 'Test Event',
            description: 'This is a test event.',
            startsAt: now()->addDays(10)->toImmutable(),
            endsAt: now()->addDays(11)->toImmutable(),
            applicationDeadline: now()->addDays(5)->toImmutable(),
            participantLimit: 100,
            location: 'Test Location'
        );

        $event = app(CreateEvent::class)->execute(
            $organizer,
            $data
        );

        $this->assertSame('Test Event', $event->title);
        $this->assertSame('This is a test event.', $event->description);
        $this->assertsame('Test Location', $event->location);
        $this->assertSame(100, $event->participant_limit);

        $this->assertSame(EventStatus::DRAFT, $event->status);
        $this->assertTrue($event->organizer->is($organizer));

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'organizer_id' => $organizer->id,
            'title' => 'Test Event',
            'participant_limit' => 100,
        ]);
    }

    public function test_event_cannot_end_before_it_starts(): void
    {
        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        $data = new CreateEventData(
            title: 'Test Event',
            description: 'This is a test event.',
            startsAt: now()->addDays(10)->toImmutable(),
            endsAt: now()->addDays(9)->toImmutable(),
            applicationDeadline: now()->addDays(5)->toImmutable(),
            participantLimit: 100,
            location: 'Test Location'
        );

        $this->expectException(\DomainException::class);

        app(CreateEvent::class)->execute(
            $organizer,
            $data
        );
    }

    public function test_event_cannot_have_zero_participant_limit(): void
    {
        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        $data = new CreateEventData(
            title: 'Test Event',
            description: 'This is a test event.',
            startsAt: now()->addDays(10)->toImmutable(),
            endsAt: now()->addDays(11)->toImmutable(),
            applicationDeadline: now()->addDays(5)->toImmutable(),
            participantLimit: 0,
            location: 'Test Location'
        );

        $this->expectException(\DomainException::class);

        app(CreateEvent::class)->execute(
            $organizer,
            $data
        );
    }

    public function test_event_cannot_have_negative_participant_limit(): void
    {
        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        $data = new CreateEventData(
            title: 'Test Event',
            description: 'This is a test event.',
            startsAt: now()->addDays(10)->toImmutable(),
            endsAt: now()->addDays(11)->toImmutable(),
            applicationDeadline: now()->addDays(5)->toImmutable(),
            participantLimit: -10,
            location: 'Test Location',
        );

        $this->expectException(\DomainException::class);
        app(CreateEvent::class)->execute(
            $organizer,
            $data
        );
    }

    public function test_event_can_have_null_participant_limit(): void
    {
        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        $data = new CreateEventData(
            title: 'Test Event',
            description: 'This is a test event.',
            startsAt: now()->addDays(10)->toImmutable(),
            endsAt: now()->addDays(11)->toImmutable(),
            applicationDeadline: now()->addDays(5)->toImmutable(),
            participantLimit: null,
            location: 'Test Location'
        );

        $event = app(CreateEvent::class)->execute(
            $organizer,
            $data
        );

        $this->assertNull($event->participantLimit);
    }

    public function test_event_can_have_null_application_deadline(): void
    {
        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        $data = new CreateEventData(
            title: 'Test Event',
            description: 'This is a test event.',
            startsAt: now()->addDays(10)->toImmutable(),
            endsAt: now()->addDays(11)->toImmutable(),
            applicationDeadline: null,
            participantLimit: 100,
            location: 'Test Location'
        );

        $event = app(CreateEvent::class)->execute(
            $organizer,
            $data
        );

        $this->assertNull($event->applicationDeadline);
    }

    public function test_event_cannot_have_application_deadline_after_start_date(): void
    {
        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        $data = new CreateEventData(
            title: 'Test Event',
            description: 'This is a test event.',
            startsAt: now()->addDays(10)->toImmutable(),
            endsAt: now()->addDays(11)->toImmutable(),
            applicationDeadline: now()->addDays(12)->toImmutable(),
            participantLimit: 100,
            location: 'Test Location'
        );

        $this->expectException(\DomainException::class);

        app(CreateEvent::class)->execute(
            $organizer,
            $data
        );
    }
}
