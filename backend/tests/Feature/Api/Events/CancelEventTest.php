<?php

namespace Tests\Feature\Api\Events;

use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelEventTest extends TestCase
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
            'location' => 'TestLocation',
            'participant_limit' => 100,
            'status' => EventStatus::PUBLISHED->value,
        ]);
    }

    public function test_organizer_can_cancel_event(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        $organizer->users()->attach($user);
        $event = $this->createEvent($organizer);

        $this->actingAs($user)
            ->postJson("/api/events/{$event->id}/cancel")
            ->assertOk();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => EventStatus::CANCELLED->value,
        ]);
    }

    public function test_guest_cannot_cancel_event(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);

        $this->postJson("/api/events/{$event->id}/cancel")
            ->assertUnauthorized();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => EventStatus::PUBLISHED->value,
        ]);
    }

    public function test_non_organizer_cannot_cancel_event(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);

        $this->actingAs($user)
            ->postJson("/api/events/{$event->id}/cancel")
            ->assertForbidden();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => EventStatus::PUBLISHED->value,
        ]);
    }

    public function test_organizer_cannot_cancel_draft_event(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        $organizer->users()->attach($user);
        $event = Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Draft Event',
            'description' => 'Draft Description',
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(11),
            'application_deadline' => now()->addDays(5),
            'location' => 'Draft Location',
            'participant_limit' => 100,
            'status' => EventStatus::DRAFT->value,
        ]);

        $this->actingAs($user)
            ->postJson("/api/events/{$event->id}/cancel")
            ->assertUnprocessable();
    }

    public function test_cannot_cancel_nonexistent_event(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson('/api/events/999999/cancel')
            ->assertNotFound();
    }

    public function test_organizer_cannot_cancel_event_which_is_already_cancelled(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        $organizer->users()->attach($user);
        $event = Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Cancelled Event',
            'description' => 'Cancelled Description',
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(11),
            'application_deadline' => now()->addDays(5),
            'location' => 'Cancelled Location',
            'participant_limit' => 100,
            'status' => EventStatus::CANCELLED->value,
        ]);

        $this->actingAs($user)
            ->postJson("/api/events/{$event->id}/cancel")
            ->assertUnprocessable();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => EventStatus::CANCELLED->value,
        ]);
    }
}
