<?php

namespace Tests\Feature\Policies;

use App\Enums\EventApplicationStatus;
use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\EventApplication;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventApplicationPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function createOrganizer(): Organizer
    {
        return Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);
    }

    private function createEvent(Organizer $organizer): Event
    {
        return Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Test Event',
            'description' => 'Test Description',
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(11),
            'location' => 'Test Location',
            'participant_limit' => 100,
            'status' => EventStatus::PUBLISHED,

        ]);
    }

    private function createEventApplication(User $user, Event $event): EventApplication
    {
        return EventApplication::forceCreate([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::PENDING,
        ]);
    }

    public function test_user_can_cancel_own_event_application(): void
    {
        $user = User::factory()->create();
        $event = $this->createEvent($this->createOrganizer());
        $eventApplication = $this->createEventApplication($user, $event);
        $user2 = User::factory()->create();
        $this->assertFalse($user2->can('cancel', $eventApplication));
        $this->assertTrue($user->can('cancel', $eventApplication));
    }

    /**
     * @test
     */
    public function test_user_cannot_cancel_others_event_application(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $event = $this->createEvent($this->createOrganizer());
        $eventApplication = $this->createEventApplication($user1, $event);

        $this->assertTrue($user1->can('cancel', $eventApplication));
        $this->assertFalse($user2->can('cancel', $eventApplication));
    }

    public function test_organizer_can_accept_event_application(): void
    {
        $user = User::factory()->create();
        $user2 = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $organizer->users()->attach($user->id);
        $eventApplication = $this->createEventApplication($user2, $event);
        $this->assertTrue($user->can('accept', $eventApplication));
    }

    public function test_organizer_can_reject_event_application(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $organizer->users()->attach($user1->id);
        $eventApplication = $this->createEventApplication($user2, $event);
        $this->assertTrue($user1->can('reject', $eventApplication));
    }

    public function test_non_organizer_cannot_accept_event_application(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $eventApplication = $this->createEventApplication($user2, $event);
        $otherOrganizer = $this->createOrganizer();
        $otherOrganizer->users()->attach($user1->id);

        $this->assertFalse($user1->can('accept', $eventApplication));
    }

    public function test_non_organizer_cannot_reject_event_application(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $eventApplication = $this->createEventApplication($user2, $event);
        $otherOrganizer = $this->createOrganizer();
        $otherOrganizer->users()->attach($user1->id);
        $this->assertFalse($user1->can('reject', $eventApplication));
    }
}
