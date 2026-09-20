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

class ApplyToEventsTest extends TestCase
{
    use RefreshDatabase;

    private function createOrganizer(): Organizer
    {
        return Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);
    }

    private function createEvent(Organizer $organizer, EventStatus $status = EventStatus::PUBLISHED): Event
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
            'status' => $status->value,
        ]);
    }

    public function test_user_can_apply_to_event(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $this->actingAs($user)->postJson("/api/events/{$event->id}/applications")
            ->assertStatus(201)
            ->assertJsonPath('status', EventApplicationStatus::PENDING->value);

        $this->assertDatabaseHas('event_applications', [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::PENDING->value,
        ]);
    }

    public function test_guest_cannot_apply_to_event(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $this->postJson("/api/events/{$event->id}/applications")
            ->assertStatus(401);

        $this->assertDatabaseMissing('event_applications', [
            'event_id' => $event->id,
        ]);
    }

    public function test_user_cannot_apply_to_event_after_deadline(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $event->application_deadline = now()->subDays(1);
        $event->save();

        $this->actingAs($user)
            ->postJson("/api/events/{$event->id}/applications")
            ->assertUnprocessable();

        $this->assertDatabaseMissing('event_applications', [
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);
    }

    public function test_user_cannot_apply_to_event_if_already_applied(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);

        EventApplication::forceCreate([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => EventApplicationStatus::PENDING->value,
        ]);

        $this->actingAs($user)
            ->postJson("/api/events/{$event->id}/applications")
            ->assertUnprocessable();

        $this->assertDatabaseCount('event_applications', 1);
    }

    public function test_me_event_applications_returns_user_applications(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganizer();
        for ($i = 0; $i < 3; $i++) {
            $event = $this->createEvent($organizer);
            EventApplication::forceCreate([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'status' => EventApplicationStatus::PENDING->value,
            ]);
        }

        $user2 = User::factory()->create();
        EventApplication::forceCreate([
            'user_id' => $user2->id,
            'event_id' => Event::first()->id,
            'status' => EventApplicationStatus::PENDING->value,
        ]);

        $this->actingAs($user)
            ->getJson('/api/me/event-applications')
            ->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonMissing(['user_id' => $user2->id])
            ->assertJsonFragment(['user_id' => $user->id]);

        $this->actingAs($user2)
            ->getJson('/api/me/event-applications')
            ->assertStatus(200)
            ->assertJsonMissing(['user_id' => $user->id])
            ->assertJsonCount(1);
    }
}
