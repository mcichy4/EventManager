<?php

namespace Tests\Feature\Api\Events;

use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UpdateEventTest extends TestCase
{
    use RefreshDatabase;

    private function createEvent(EventStatus $status = EventStatus::DRAFT): Event
    {
        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY
        ]);

        return Event::forceCreate([
            'organizer_id'=> $organizer->id,
            'title' => 'Old title',
            'description' => 'Test description',
            'status' => $status,
            'starts_at'=> now()->addDays(10),
            'ends_at' => now()->addDays(11),
            'application_deadline' => now()->addDays(5),
            'participant_limit' => 100,
            'location' => 'Test location',
        ]);
    }

    public function test_draft_event_can_be_update_via_api(): void
    {
        $event = $this->createEvent();
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
        $event = $this->createEvent(EventStatus::DRAFT);

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
        $event = $this->createEvent(EventStatus::PUBLISHED);

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
        $event = $this->createEvent(EventStatus::CANCELLED);

        $response = $this->patchJson(
            "/api/events/{$event->id}",
            [
                'title' => 'New title',
            ]
        );

        $response->assertUnprocessable();
    }
}
