<?php

namespace Tests\Feature\Api\Events;

use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testy pełnego przepływu PATCH: uwierzytelnianie, policy, walidacja, akcja i zapis.
 * actingAs ustawia użytkownika testowego, a attach nadaje mu członkostwo u organizatora.
 * assertDatabaseHas sprawdza zapis, nie tylko kod odpowiedzi HTTP.
 */
class UpdateEventTest extends TestCase
{
    use RefreshDatabase;

    private function createEvent(EventStatus $status = EventStatus::DRAFT): Event
    {
        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        return Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Old title',
            'description' => 'Test description',
            'status' => $status,
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(11),
            'application_deadline' => now()->addDays(5),
            'participant_limit' => 100,
            'location' => 'Test location',
        ]);
    }

    public function test_deadline_can_be_updated_without_sending_start_via_api(): void
    {
        $event = $this->createEvent(EventStatus::DRAFT);
        $user = User::factory()->create();
        $event->organizer->users()->attach($user);
        $this->actingAs($user);
        $deadline = $event->starts_at->copy()->subDay()->toDateTimeString();

        $this->patchJson("/api/events/{$event->id}", [
            'application_deadline' => $deadline,
        ])->assertOk();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'application_deadline' => $deadline,
            'starts_at' => $event->starts_at->toDateTimeString(),
        ]);
    }

    public function test_deadline_can_be_cleared_via_api(): void
    {
        $event = $this->createEvent(EventStatus::DRAFT);
        $user = User::factory()->create();
        $event->organizer->users()->attach($user);
        $this->actingAs($user);

        $this->patchJson("/api/events/{$event->id}", [
            'application_deadline' => null,
        ])->assertOk();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'application_deadline' => null,
        ]);
    }

    public function test_deadline_after_existing_start_is_rejected_via_api(): void
    {
        $event = $this->createEvent(EventStatus::DRAFT);
        $user = User::factory()->create();
        $event->organizer->users()->attach($user);
        $this->actingAs($user);

        $this->patchJson("/api/events/{$event->id}", [
            'application_deadline' => $event->starts_at->copy()->addDay()->toDateTimeString(),
            'title' => 'Should not be saved',
        ])->assertUnprocessable()->assertJson([
            'message' => 'Application deadline cannot be after the event starts.',
        ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => $event->title,
            'application_deadline' => $event->application_deadline->toDateTimeString(),
        ]);
    }

    public function test_draft_event_can_be_update_via_api(): void
    {
        $user = User::factory()->create();
        $event = $this->createEvent();
        $event->organizer->users()->attach($user);
        $this->actingAs($user);

        $response = $this->patchJson(
            "/api/events/{$event->id}", [
                'title' => 'New title',

            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'New title',
        ]);
    }

    public function test_event_cannot_be_updated_with_invalid_data(): void
    {
        $user = User::factory()->create();
        $event = $this->createEvent(EventStatus::DRAFT);
        $event->organizer->users()->attach($user);
        $this->actingAs($user);

        $response = $this->patchJson(
            "/api/events/{$event->id}",
            [
                'title' => '',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_published_event_cannot_be_updated_via_api(): void
    {
        $user = User::factory()->create();
        $event = $this->createEvent(EventStatus::PUBLISHED);
        $event->organizer->users()->attach($user);
        $this->actingAs($user);

        $response = $this->patchJson(
            "/api/events/{$event->id}",
            [
                'title' => 'New title',
            ]
        );

        $response->assertUnprocessable();
    }

    public function test_cancelled_event_cannot_be_updated_via_api(): void
    {
        $user = User::factory()->create();
        $event = $this->createEvent(EventStatus::CANCELLED);
        $event->organizer->users()->attach($user);
        $this->actingAs($user);
        $response = $this->patchJson(
            "/api/events/{$event->id}",
            [
                'title' => 'New title',
            ]
        );

        $response->assertUnprocessable();
    }

    public function test_guest_cannot_update_event(): void
    {
        $event = $this->createEvent(EventStatus::DRAFT);

        $response = $this->patchJson("/api/events/{$event->id}", [
            'title' => 'New title',
        ]);

        $response->assertUnauthorized();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Old title',
        ]);
    }

    public function test_user_not_associated_with_organizer_cannot_update_event(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $event = $this->createEvent(EventStatus::DRAFT);

        $response = $this->patchJson("/api/events/{$event->id}", [
            'title' => 'New title',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Old title',
        ]);
    }

    public function test_event_description_can_be_updated_via_api(): void
    {
        $user = User::factory()->create();
        $event = $this->createEvent(EventStatus::DRAFT);
        $event->organizer->users()->attach($user);
        $this->actingAs($user);

        $response = $this->patchJson(
            "/api/events/{$event->id}", [
                'description' => 'New description',
            ]
        );

        $response->assertOK();
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'description' => 'New description',
        ]);

    }

    public function test_event_description_can_be_cleared_via_api(): void
    {
        $user = User::factory()->create();
        $event = $this->createEvent(EventStatus::DRAFT);
        $event->organizer->users()->attach($user);
        $this->actingAs($user);

        $response = $this->patchJson(
            "/api/events/{$event->id}", [
                'description' => null,
            ]
        );

        $response->assertOK();
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'description' => null,
        ]);
    }

    public function test_event_ends_at_can_be_updated_without_changing_starts_at_via_api(): void
    {
        $user = User::factory()->create();
        $event = $this->createEvent(EventStatus::DRAFT);
        $event->organizer->users()->attach($user);
        $this->actingAs($user);

        $newEndsAt = $event->ends_at->copy()->addDay()->toDateTimeString();
        $response = $this->patchJson(
            "/api/events/{$event->id}", [
                'ends_at' => $newEndsAt,
            ]
        );

        $response->assertOK();
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'starts_at' => $event->starts_at,
            'ends_at' => $newEndsAt,
        ]);
    }

    public function test_event_starts_at_cannot_be_after_ends_at_via_api(): void
    {
        $user = User::factory()->create();
        $event = $this->createEvent(EventStatus::DRAFT);
        $event->organizer->users()->attach($user);
        $this->actingAs($user);

        $response = $this->patchJson(
            "/api/events/{$event->id}", [
                'starts_at' => now()->addDays(12)->toDateTimeString(),
                'ends_at' => now()->addDays(11)->toDateTimeString(),
            ]);

        $response->assertUnprocessable();
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'starts_at' => $event->starts_at,
            'ends_at' => $event->ends_at,
        ]);
    }

    public function test_event_starts_at_cannot_be_equal_to_ends_at_via_api(): void
    {
        $user = User::factory()->create();
        $event = $this->createEvent(EventStatus::DRAFT);
        $event->organizer->users()->attach($user);
        $this->actingAs($user);
        
        $response = $this->patchJson(
            "/api/events/{$event->id}", [
                'ends_at' => $event->starts_at->toDateTimeString(),
            ]
        );

        $response->assertUnprocessable();
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'starts_at' => $event->starts_at,
            'ends_at' => $event->ends_at,
        ]);
    }

    public function test_start_after_existing_end_is_rejected_via_api(): void
    {
        $user = User::factory()->create();
        $event = $this->createEvent(EventStatus::DRAFT);
        $event->organizer->users()->attach($user);
        $this->actingAs($user);

        $response = $this->patchJson(
            "/api/events/{$event->id}", [
                'starts_at' => $event->ends_at->copy()->addDay()->toDateTimeString(),
            ]
        );

        $response->assertUnprocessable();
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'starts_at' => $event->starts_at,
            'ends_at' => $event->ends_at,
        ]);
        
    }
}
