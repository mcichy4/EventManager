<?php

namespace Tests\Feature\Actions\Events;

use App\Actions\Events\AcceptEventApplication;
use App\Enums\EventApplicationStatus;
use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\EventApplication;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcceptEventApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_application_can_be_accepted(): void
    {
        $application = $this->createEventApplication();
        // $application = EventApplication::factory()->create([
        //     'status' => EventApplicationStatus::PENDING
        // ]);

        $result = app(AcceptEventApplication::class)->execute($application);

        $this->assertSame(
            EventApplicationStatus::ACCEPTED,
            $result->status
        );

        $this->assertDatabaseHas('event_applications', [
            'id' => $result->id,
            'status' => EventApplicationStatus::ACCEPTED->value,
        ]);
    }

    public function test_non_pending_application_cannot_be_accepted(): void
    {
        $application = $this->createEventApplication(EventApplicationStatus::ACCEPTED);

        $this->expectException(\DomainException::class);
        app(AcceptEventApplication::class)->execute($application);
    }

    public function test_application_cannot_be_accepted_for_finished_event(): void
    {
        $application = $this->createEventApplication(
            EventApplicationStatus::PENDING,
            null,
            now()->subDays(2),
            now()->subDay(),
        );

        $this->expectException(\DomainException::class);
        app(AcceptEventApplication::class)->execute($application);
    }

    public function test_application_cannot_be_accepted_if_participant_limit_reached(): void
    {
        $application = $this->createEventApplication(participantLimit: 1);
        EventApplication::forceCreate([
            'event_id' => $application->event_id,
            'status' => EventApplicationStatus::ACCEPTED,
            'user_id' => User::factory()->create()->id,
        ]);

        $this->expectException(\DomainException::class);
        app(AcceptEventApplication::class)->execute($application);
    }

    private function createEventApplication(
        EventApplicationStatus $status = EventApplicationStatus::PENDING,
        ?int $participantLimit = null,
        $startsAt = null,
        $endsAt = null
    ): EventApplication {
        $user = User::factory()->create();

        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        $event = Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Test Event',
            'description' => 'Test Description',
            'starts_at' => $startsAt ?? now()->addDays(1),
            'ends_at' => $endsAt ?? now()->addDays(2),
            'location' => 'Test Location',
            'participant_limit' => $participantLimit,
            'status' => EventStatus::PUBLISHED,
        ]);

        return EventApplication::forceCreate([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => $status,
        ]);
    }
}
