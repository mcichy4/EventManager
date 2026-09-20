<?php

namespace Tests\Feature\Api\Events;

use App\Actions\Events\ApplyToEvent;
use App\Actions\Events\CreateEvent;
use App\Actions\Events\PublishEvent;
use App\Actions\Organizers\AddOrganizerMember;
use App\Actions\Organizers\CreateOrganizer;
use App\Data\Events\CreateEventData;
use App\Enums\EventApplicationStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListApplicationsTest extends TestCase
{
    use RefreshDatabase;

    private function createOrganizer(User $owner): Organizer
    {
        return app(CreateOrganizer::class)->execute(
            $owner,
            'Test Organizer',
            OrganizerType::COMPANY->value,
            'Test description'
        );
    }

    private function createEvent(Organizer $organizer, string $title): Event
    {
        $startsAt = CarbonImmutable::now()->addDays(random_int(10, 20));

        return app(CreateEvent::class)->execute(
            $organizer,
            new CreateEventData(
                title: 'Event: '.$title,
                description: 'Test description for event '.$title,
                startsAt: $startsAt,
                endsAt: $startsAt->addDay(),
                applicationDeadline: $startsAt->subDays(random_int(3, 5)),
                location: 'Test location for event '.$title,
                participantLimit: random_int(10, 100),
            )
        );
    }

    public function test_list_applications(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);
        $event = $this->createEvent($organizer, 'first');
        app(PublishEvent::class)->execute($event);

        $applicant1 = User::factory()->create();
        $applicant2 = User::factory()->create();

        app(ApplyToEvent::class)->execute($applicant1, $event);
        app(ApplyToEvent::class)->execute($applicant2, $event);

        $response = $this
            ->actingAs($owner)
            ->getJson("/api/events/{$event->id}/applications")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'user_id' => $applicant1->id,
                'name' => $applicant1->name,
                'status' => EventApplicationStatus::PENDING->value,
            ])
            ->assertJsonFragment([
                'name' => $applicant2->name,
                'status' => EventApplicationStatus::PENDING->value,
            ]);
    }

    public function test_member_of_organizer_can_list_applications(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);
        $member = User::factory()->create();
        app(AddOrganizerMember::class)->execute($member, $organizer);

        $event = $this->createEvent($organizer, 'first');
        app(PublishEvent::class)->execute($event);

        $applicant = User::factory()->create();
        app(ApplyToEvent::class)->execute($applicant, $event);

        $response = $this
            ->actingAs($member)
            ->getJson("/api/events/{$event->id}/applications")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'user_id' => $applicant->id,
                'name' => $applicant->name,
                'status' => EventApplicationStatus::PENDING->value,
            ]);
    }

    public function test_non_member_cannot_list_applications(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);
        $event = $this->createEvent($organizer, 'first');
        app(PublishEvent::class)->execute($event);

        $applicant = User::factory()->create();
        app(ApplyToEvent::class)->execute($applicant, $event);

        $nonMember = User::factory()->create();

        $response = $this
            ->actingAs($nonMember)
            ->getJson("/api/events/{$event->id}/applications")
            ->assertForbidden();

        $response->assertJsonMissing([
            'user_id' => $applicant->id,
            'name' => $applicant->name,
            'status' => EventApplicationStatus::PENDING->value,
        ]);
    }

    public function test_unauthenticated_user_cannot_list_applications(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);
        $event = $this->createEvent($organizer, 'first');
        app(PublishEvent::class)->execute($event);
        $applicant = User::factory()->create();
        app(ApplyToEvent::class)->execute($applicant, $event);

        $response = $this
            ->getJson("/api/events/{$event->id}/applications")
            ->assertUnauthorized();

        $response->assertJsonMissing([
            'user_id' => $applicant->id,
            'name' => $applicant->name,
            'status' => EventApplicationStatus::PENDING->value,
        ]);
    }

    public function test_event_non_existent_returns_not_found(): void
    {
        $owner = User::factory()->create();
        $nonExistentEventId = 999999;

        $response = $this
            ->actingAs($owner)
            ->getJson("/api/events/{$nonExistentEventId}/applications")
            ->assertNotFound();
    }

    public function test_pagination_of_applications(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);
        $event = $this->createEvent($organizer, 'first');
        app(PublishEvent::class)->execute($event);

        for ($i = 0; $i <= 14; $i++) {
            app(ApplyToEvent::class)->execute(User::factory()->create(), $event);
        }

        $response = $this
            ->actingAs($owner)
            ->getJson("/api/events/{$event->id}/applications?page=1")
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonFragment([
                'current_page' => 1,
                'per_page' => 10,
                'total' => 15,
            ]);

        $response = $this
            ->actingAs($owner)
            ->getJson("/api/events/{$event->id}/applications?page=2")
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonFragment([
                'current_page' => 2,
                'per_page' => 10,
                'total' => 15,
            ]);
    }
}
