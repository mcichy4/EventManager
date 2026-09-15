<?php

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testy publikacji przez HTTP: gość otrzymuje 401, obcy użytkownik 403, niedozwolony status 422.
 * Członek organizacji może opublikować szkic; sprawdzamy też wynik zapisany w bazie.
 */
class PublishEventControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createEvent(EventStatus $status): Event
    {
        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        return Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Event Title',
            'description' => 'Event description',
            'status' => $status,
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(11),
            'application_deadline' => now()->addDays(6),
            'participant_limit' => 100,
            'location' => 'Test Location',
        ]);
    }

    public function test_draft_event_can_be_publish_via_api(): void
    {

        $user = User::factory()->create();

        $event = $this->createEvent(EventStatus::DRAFT);
        $event->organizer->users()->attach($user);
        $this->actingAs($user);

        $response = $this->postJson(
            "/api/events/{$event->id}/publish",
        );

        $response->assertOk();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => EventStatus::PUBLISHED->value,
        ]);
    }

    public function test_published_event_cannot_be_publish_again_via_api(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $event = $this->createEvent(EventStatus::PUBLISHED);
        $event->organizer->users()->attach($user);
        $response = $this->postJson(
            "/api/events/{$event->id}/publish",
        );

        $response->assertUnprocessable();
    }

    public function test_cancelled_event_cannot_be_published_via_api(): void
    {
        $user = User::factory()->create();
        $event = $this->createEvent(EventStatus::CANCELLED);
        $event->organizer->users()->attach($user);
        $this->actingAs($user);
        $response = $this->postJson(
            "/api/events/{$event->id}/publish",
        );

        $response->assertUnprocessable();
    }

    public function test_guest_cannot_publish_event_via_api(): void
    {
        $event = $this->createEvent(EventStatus::DRAFT);

        $response = $this->postJson(
            "/api/events/{$event->id}/publish",
        );
        $response->assertUnauthorized();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => EventStatus::DRAFT->value,
        ]);
    }

    public function test_user_not_associated_with_organizer_cannot_publish_event_via_api(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $event = $this->createEvent(EventStatus::DRAFT);
        $response = $this->postJson(
            "/api/events/{$event->id}/publish",
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => EventStatus::DRAFT->value,
        ]);
    }
}
