<?php

namespace Tests\Feature\Actions\Events;

use App\Actions\Events\AcceptEventApplication;
use App\Enums\EventApplicationStatus;
use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\EventApplication;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprawdza akceptację, niedozwolony status, zakończone wydarzenie i wyczerpany limit miejsc.
 * Akcja jest wywoływana bez HTTP: te testy nie sprawdzają logowania ani policy.
 * RefreshDatabase izoluje dane kolejnych testów; asercje sprawdzają wynik lub oczekiwany wyjątek.
 */
class AcceptEventApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function createEventApplication(
        EventApplicationStatus $status = EventApplicationStatus::PENDING,
        ?int $participantLimit = null,
        $startsAt = null,
        $endsAt = null
    ): EventApplication {
        $user = User::factory()->create();

        $organizer = Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);

        $event = Event::forceCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Test Event',
            'description' => 'Test Description',
            'starts_at' => $startsAt ?? now()->addDays(1),
            'ends_at' => $endsAt ?? now()->addDays(2),
            'location' => 'Test Location',
            'participant_limit' => $participantLimit,
            'status' => EventStatus::PUBLISHED,
        ]);

        return EventApplication::forceCreate([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => $status,
        ]);
    }

    public function test_pending_application_can_be_accepted(): void
    {
        $application = $this->createEventApplication();

        $result = app(AcceptEventApplication::class)->execute($application);

        $this->assertSame(
            EventApplicationStatus::ACCEPTED,
            $result->status
        );

        $this->assertDatabaseHas('event_applications', [
            'id' => $result->id,
            'status' => EventApplicationStatus::ACCEPTED->value,
        ]);
    }

    public function test_non_pending_application_cannot_be_accepted(): void
    {
        $application = $this->createEventApplication(EventApplicationStatus::ACCEPTED);

        $this->expectException(\DomainException::class);
        app(AcceptEventApplication::class)->execute($application);
    }

    public function test_application_cannot_be_accepted_for_finished_event(): void
    {
        $application = $this->createEventApplication(
            EventApplicationStatus::PENDING,
            null,
            now()->subDays(2),
            now()->subDay(),
        );

        $this->expectException(\DomainException::class);
        app(AcceptEventApplication::class)->execute($application);
    }

    public function test_application_cannot_be_accepted_if_participant_limit_reached(): void
    {
        $application = $this->createEventApplication(participantLimit: 1);
        EventApplication::forceCreate([
            'event_id' => $application->event_id,
            'status' => EventApplicationStatus::ACCEPTED,
            'user_id' => User::factory()->create()->id,
        ]);

        $this->expectException(\DomainException::class);
        app(AcceptEventApplication::class)->execute($application);
    }

    public function test_capacity_of_another_event_does_not_block_acceptance(): void
    {
        $this->createEventApplication(
            status: EventApplicationStatus::ACCEPTED,
            participantLimit: 1
        );

        $application = $this->createEventApplication(participantLimit: 1);
        app(AcceptEventApplication::class)->execute($application);

        $this->assertDatabaseHas('event_applications', [
            'id' => $application->id,
            'event_id' => $application->event_id,
            'status' => EventApplicationStatus::ACCEPTED->value,
        ]);
    }

    public function test_acceptation_cannot_accepted_for_cancelled_event(): void
    {
        $application = $this->createEventApplication();
        $event = $application->event;
        $event->status = EventStatus::CANCELLED;
        $event->save();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot accept applications for events that are not published.');
        app(AcceptEventApplication::class)->execute($application);
    }

    public function test_application_cannot_be_accepted_for_draft_event(): void
    {
        $application = $this->createEventApplication();
        $event = $application->event;
        $event->status = EventStatus::DRAFT;
        $event->save();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot accept applications for events that are not published.');

        app(AcceptEventApplication::class)->execute($application);
    }

    public function test_application_cannot_be_accepted_when_status_is_rejected(): void
    {
        $application = $this->createEventApplication(EventApplicationStatus::REJECTED);
        
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only pending applications can be accepted.');

        app(AcceptEventApplication::class)->execute($application);
    }

    public function test_application_cannot_be_accepted_when_status_is_cancelled(): void
    {
        $application = $this->createEventApplication(EventApplicationStatus::CANCELLED);
        
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only pending applications can be accepted.');
        
        app(AcceptEventApplication::class)->execute($application);
    }

    public function test_pending_application_do_not_take_up_capacity(): void
    {
        $application1 = $this->createEventApplication(participantLimit: 1);
        $application2 = EventApplication::forceCreate([
            'event_id' => $application1->event_id,
            'user_id' => User::factory()->create()->id,
            'status' => EventApplicationStatus::PENDING,
        ]);

        app(AcceptEventApplication::class)->execute($application1);
        $this->assertDatabaseHas('event_applications', [
            'id' => $application1->id,
            'status' => EventApplicationStatus::ACCEPTED->value,
        ]);

        $this->assertDatabaseHas('event_applications', [
            'id' => $application2->id,
            'status' => EventApplicationStatus::PENDING->value,
        ]);
    }

    public function test_rejected_application_do_not_take_up_capacity(): void
    {
        $application1 = $this->createEventApplication(participantLimit: 1);
        $application2 = EventApplication::forceCreate([
            'event_id' => $application1->event_id,
            'user_id' => User::factory()->create()->id,
            'status' => EventApplicationStatus::REJECTED,
        ]);

        app(AcceptEventApplication::class)->execute($application1);
        $this->assertDatabaseHas('event_applications', [
            'id' => $application1->id,
            'status' => EventApplicationStatus::ACCEPTED->value,
        ]);

        $this->assertDatabaseHas('event_applications', [
            'id' =>$application2->id,
            'status' => EventApplicationStatus::REJECTED->value,
        ]);
    }

    public function test_cancelled_application_do_not_take_up_capacity(): void
    {
        $application1 = $this->createEventApplication(participantLimit: 1);
        $application2 = EventApplication::forceCreate([
            'event_id' =>$application1->event_id,
            'user_id' => User::factory()->create()->id,
            'status' => EventApplicationStatus::CANCELLED,
        ]);

        app(AcceptEventApplication::class)->execute($application1);
        $this->assertDatabaseHas('event_applications', [
            'id' => $application1->id,
            'status' => EventApplicationStatus::ACCEPTED->value,
        ]);
        
        $this->assertDatabaseHas('event_applications', [
            'id' => $application2->id,
            'status' => EventApplicationStatus::CANCELLED->value,
        ]);
    }
}
