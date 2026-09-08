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

class ApplyToEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_apply_to_published_event(): void
    {
        $user = User::factory()->create();

        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        $event = Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Test Event',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
            'location' => 'Warsaw',
            'status' => EventStatus::PUBLISHED,
        ]);

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

    public function test_user_cannot_apply_to_unpublished_event(): void
    {
        $user = User::factory()->create();

        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        $event = Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Test Event',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
            'location' => 'Warsaw',
            'status' => EventStatus::DRAFT,
        ]);

        $this->expectException(DomainException::class);

        app(ApplyToEvent::class)->execute($user, $event);
    }

    public function test_user_cannot_apply_to_finished_event(): void
    {
        $user = User::factory()->create();

        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        $event = Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Finished Event',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
            'location' => 'Warsaw',
            'status' => EventStatus::PUBLISHED,
        ]);

        $this->expectException(DomainException::class);

        app(ApplyToEvent::class)->execute($user, $event);
    }

    public function test_user_cannot_apply_twice_to_same_event(): void
    {
        $user = User::factory()->create();

        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        $event = Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Test Event',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
            'location' => 'Warsaw',
            'status' => EventStatus::PUBLISHED,
        ]);

        $action = app(ApplyToEvent::class);

        $action->execute($user, $event);

        $this->expectException(DomainException::class);

        $action->execute($user, $event);
    }
}