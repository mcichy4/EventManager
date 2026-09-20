<?php

namespace Tests\Feature\Api\Events;

use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteEventTest extends TestCase
{
    use RefreshDatabase;

    private function createOrganizer(): Organizer
    {
        return Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);
    }

    private function createEvent(Organizer $organizer, EventStatus $status = EventStatus::DRAFT): Event
    {
        return Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Test Event',
            'description' => 'Test Description',
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(11),
            'application_deadline' => now()->addDays(4),
            'location' => 'Test Location',
            'participant_limit' => 100,
            'status' => $status->value,
        ]);
    }

    public function test_organizer_can_delete_event(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer, EventStatus::DRAFT);
        $organizer->users()->attach($user);
        $this->actingAs($user);
        $response = $this->deleteJson("/api/events/{$event->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);
    }

    public function test_guest_cannot_delete_draft_event(): void
    {
        $event = $this->createEvent($this->createOrganizer(), EventStatus::DRAFT);
        $response = $this->deleteJson("/api/events/{$event->id}");
        $response->assertUnauthorized();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
        ]);
    }

    public function test_organizer_cannot_delete_other_organizer_event(): void
    {
        $user = User::factory()->create();
        $otherOrganizer = $this->createOrganizer();
        $otherOrganizer->users()->attach($user);

        $this->actingAs($user);
        $event = $this->createEvent($this->createOrganizer(), EventStatus::DRAFT);

        $response = $this->deleteJson("/api/events/{$event->id}");
        $response->assertForbidden();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
        ]);
    }

    public function test_organizer_cannot_delete_published_event(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        $organizer->users()->attach($user);
        $event = $this->createEvent($organizer, EventStatus::PUBLISHED);

        $this->actingAs($user);
        $response = $this->deleteJson("/api/events/{$event->id}");
        $response->assertUnprocessable();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => EventStatus::PUBLISHED->value,
        ]);
    }

    public function test_organizer_cannot_delete_cancelled_event(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        $organizer->users()->attach($user);
        $event = $this->createEvent($organizer, EventStatus::CANCELLED);

        $this->actingAs($user);
        $response = $this->deleteJson("/api/events/{$event->id}");
        $response->assertUnprocessable();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => EventStatus::CANCELLED->value,
        ]);
    }

    public function test_cannot_delete_nonexistent_event(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        $organizer->users()->attach($user);

        $this->actingAs($user);
        $response = $this->deleteJson('/api/events/999999');
        $response->assertNotFound();

        $this->assertDatabaseCount('events', 0)
            ->assertDatabaseMissing('events', [
                'id' => 999999,
            ]);
    }
}
