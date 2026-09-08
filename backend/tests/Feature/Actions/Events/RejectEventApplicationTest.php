<?php

namespace Tests\Feature\Actions\Events;

use App\Enums\OrganizerType;
use App\Enums\EventStatus;

use App\Actions\Events\RejectEventApplication;
use App\Enums\EventApplicationStatus;
use App\Models\EventApplication;
use App\Models\User;
use App\Models\Organizer;
use App\Models\Event;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RejectEventApplicationTest extends TestCase
{

    use RefreshDatabase;

    public function test_reject_pending_event_application(): void
    {
        $eventApplication = $this->createEventApplication();

        $result = app(RejectEventApplication::class)->execute($eventApplication);


        $this->assertSame(EventApplicationStatus::REJECTED,
            $result->status);

        $this->assertDatabaseHas('event_applications', [
            'id' => $result->id,
            'status' => EventApplicationStatus::REJECTED,
        ]);
    }

    public function test_reject_non_pending_event_application(): void
    {
        $eventApplication = $this->createEventApplication(EventApplicationStatus::ACCEPTED);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only pending applications can be rejected.');

        app(RejectEventApplication::class)->execute($eventApplication);
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
            'description' => 'This is a test event',
            'organizer_id' => $organizer->id,
            'participant_limit'=> null,
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
