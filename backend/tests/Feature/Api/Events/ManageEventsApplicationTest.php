<?php

namespace Tests\Feature\Api\Events;

use App\Enums\EventApplicationStatus;
use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\EventApplication;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageEventsApplicationTest extends TestCase
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
            'application_deadline' => now()->addDays(5),
            'location' => 'Test Location',
            'participant_limit' => 100,
            'status' => EventStatus::PUBLISHED->value,
        ]);
    }

    public function test_user_can_delete_their_event_application(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);

        $eventApplication = EventApplication::forceCreate([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::PENDING->value,
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/event-applications/{$eventApplication->id}")
            ->assertOk();

        $this->assertDatabaseHas('event_applications', [
            'id' => $eventApplication->id,
            'status' => EventApplicationStatus::CANCELLED->value,
        ]);
    }

    public function test_user_cannot_delete_someone_elses_event_application(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);

        $eventApplication = EventApplication::forceCreate([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::PENDING->value,
        ]);

        $this->actingAs($otherUser)
            ->deleteJson("/api/event-applications/{$eventApplication->id}")
            ->assertForbidden();
    }

    public function test_organizer_can_accept_event_application(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $userOrganizer = User::factory()->create();
        $organizer->users()->attach($userOrganizer);

        $eventApplication = EventApplication::forceCreate([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::PENDING->value,
        ]);

        $this->actingAs($userOrganizer)
            ->postJson("/api/event-applications/{$eventApplication->id}/accept")
            ->assertOk();

        $this->assertDatabaseHas('event_applications', [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::ACCEPTED->value,
        ]);
    }

    public function test_organizer_can_reject_event_application(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $userOrganizer = User::factory()->create();
        $organizer->users()->attach($userOrganizer);

        $eventApplication = EventApplication::forceCreate([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::PENDING->value,
        ]);

        $this->actingAs($userOrganizer)
            ->postJson("/api/event-applications/{$eventApplication->id}/reject")
            ->assertOk();

        $this->assertDatabaseHas('event_applications', [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::REJECTED->value,
        ]);
    }

    public function test_non_organizer_cannot_accept_someone_elses_event_application(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);

        $eventApplication = EventApplication::forceCreate([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::PENDING->value,
        ]);

        $this->actingAs($otherUser)
            ->postJson("/api/event-applications/{$eventApplication->id}/accept")
            ->assertForbidden();
        $this->assertDatabaseMissing('event_applications', [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::ACCEPTED->value,
        ]);

    }

    public function test_non_organizer_cannot_reject_someone_elses_event_application(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);

        $eventApplication = EventApplication::forceCreate([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::PENDING->value,
        ]);

        $this->actingAs($otherUser)
            ->postJson("/api/event-applications/{$eventApplication->id}/reject")
            ->assertForbidden();

        $this->assertDatabaseMissing('event_applications', [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::REJECTED->value,
        ]);
    }

    public function test_guest_cannot_accept_event_application(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);

        $eventApplication = EventApplication::forceCreate([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::PENDING->value,
        ]);

        $this->postJson("/api/event-applications/{$eventApplication->id}/accept")
            ->assertUnauthorized();

        $this->assertDatabaseMissing('event_applications', [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::ACCEPTED->value,
        ]);
    }

    public function test_guest_cannot_reject_event_application(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);

        $eventApplication = EventApplication::forceCreate([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::PENDING->value,
        ]);

        $this->postJson("/api/event-applications/{$eventApplication->id}/reject")
            ->assertUnauthorized();

        $this->assertDatabaseMissing('event_applications', [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::REJECTED->value,
        ]);
    }

    public function test_guest_cannot_delete_event_application(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);

        $eventApplication = EventApplication::forceCreate([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::PENDING->value,
        ]);

        $this->deleteJson("/api/event-applications/{$eventApplication->id}")
            ->assertUnauthorized();

        $this->assertDatabaseHas('event_applications', [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::PENDING->value,
        ]);
    }
}
