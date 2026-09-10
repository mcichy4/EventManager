<?php

namespace Tests\Feature\Actions\Events;

use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\Organizer;
use App\Actions\Events\UpdateEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;


/**
 * Eventy można edytować tylko w przypadku kiedy maja status EventStatus::DRAFT
 */
class UpdateEventTest extends TestCase
{

    use RefreshDatabase;

    private function createOldEventData(EventStatus $status): Event
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

    public function test_draft_event_can_be_updated(): void
    {
        $event = $this->createOldEventData(EventStatus::DRAFT);

        app(UpdateEvent::class)->handle($event, [
            'title' => 'New title',
        ]);

        $event->refresh();

        $this->assertSame('New title', $event->title);
        $this->assertSame(EventStatus::DRAFT, $event->status);
    }

    public function test_published_event_cannot_be_updated(): void
    {
        $event = $this->createOldEventData(EventStatus::PUBLISHED);
        
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only draft event can be updated');

        app(UpdateEvent::class)->handle($event, [
            'title' => 'New title',
        ]);
    }

    public function test_cancelled_event_cannot_be_updated(): void
    {
        $event = $this->createOldEventData(EventStatus::CANCELLED);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only draft event can be updated');

        app(UpdateEvent::class)->handle($event, [
            'title' => 'New title',
        ]);
    }
    }
