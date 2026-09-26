<?php

namespace Tests\Feature\Api\Organizers;

use App\Actions\Events\CancelEvent;
use App\Actions\Events\CreateEvent;
use App\Actions\Events\PublishEvent;
use App\Actions\Organizers\AddOrganizerMember;
use App\Actions\Organizers\CreateOrganizer;
use App\Data\Events\CreateEventData;
use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListOrganizerEventsTest extends TestCase
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
        $startsAt = CarbonImmutable::now()->addDays(random_int(5, 10));

        return app(CreateEvent::class)->execute(
            $organizer,
            new CreateEventData(
                title: 'Event: '.$title,
                description: 'Test description for event '.$title,
                startsAt: $startsAt,
                endsAt: $startsAt->addHours(random_int(8, 16)),
                applicationDeadline: $startsAt->subDays(random_int(3, 5)),
                location: 'Test location for event '.$title,
                participantLimit: random_int(10, 100),
            )
        );
    }

    public function test_list_organizer_events(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);

        $event1 = $this->createEvent($organizer, 'first');
        $event2 = $this->createEvent($organizer, 'second');
        $event3 = $this->createEvent($organizer, 'third');
        $otherOrganizer = $this->createOrganizer($owner);
        $otherEvent = $this->createEvent($otherOrganizer, 'fourth');

        app(PublishEvent::class)->execute($event2);
        app(PublishEvent::class)->execute($event3);
        app(CancelEvent::class)->handle($event3);

        $response = $this
            ->actingAs($owner)
            ->getJson("/api/organizers/{$organizer->id}/events")
            ->assertOk()
            ->assertJsonFragment([
                'id' => $event1->id,
                'title' => $event1->title,
                'description' => $event1->description,
                'location' => $event1->location,
                'status' => EventStatus::DRAFT->value,
            ])
            ->assertJsonFragment([
                'id' => $event2->id,
                'title' => $event2->title,
                'status' => EventStatus::PUBLISHED->value,
            ])
            ->assertJsonFragment([
                'id' => $event3->id,
                'title' => $event3->title,
                'status' => EventStatus::CANCELLED->value,
            ])
            ->assertJsonMissing([
                'id' => $otherEvent->id,
            ]);
    }

    public function test_organizer_member_can_list_events(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);
        $member = User::factory()->create();

        app(AddOrganizerMember::class)->execute(
            $member,
            $organizer
        );

        $event1 = $this->createEvent($organizer, 'first');
        $event2 = $this->createEvent($organizer, 'second');
        $event3 = $this->createEvent($organizer, 'third');
        app(PublishEvent::class)->execute($event2);
        app(PublishEvent::class)->execute($event3);
        app(CancelEvent::class)->handle($event3);

        $this
            ->actingAs($member)
            ->getJson("/api/organizers/{$organizer->id}/events")
            ->assertOk()
            ->assertJsonFragment([
                'id' => $event1->id,
                'title' => $event1->title,
                'status' => EventStatus::DRAFT->value,
            ])
            ->assertJsonFragment([
                'id' => $event2->id,
                'title' => $event2->title,
                'status' => EventStatus::PUBLISHED->value,
            ])
            ->assertJsonFragment([
                'id' => $event3->id,
                'title' => $event3->title,
                'status' => EventStatus::CANCELLED->value,
            ]);
    }

    public function test_non_member_cannot_list_organizer_events(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);
        $nonMember = User::factory()->create();

        $this
            ->actingAs($nonMember)
            ->getJson("/api/organizers/{$organizer->id}/events")
            ->assertForbidden();
    }

    public function test_guest_cannot_list_organizer_events(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);

        $this
            ->getJson("/api/organizers/{$organizer->id}/events")
            ->assertUnauthorized();
    }

    public function test_non_existent_organizer_returns_not_found(): void
    {
        $nonExistentOrganizerId = 999;

        $this
            ->actingAs(User::factory()->create())
            ->getJson("/api/organizers/{$nonExistentOrganizerId}/events")
            ->assertNotFound();
    }

    public function test_pagination_of_organizer_events(): void
    {
        $owner = User::factory()->create();
        $organizer = $this->createOrganizer($owner);

        for ($i = 1; $i <= 11; $i++) {
            $this->createEvent($organizer, 'event '.$i);
        }

        $response = $this
            ->actingAs($owner)
            ->getJson("/api/organizers/{$organizer->id}/events?page=1&per_page=10")
            ->assertOk();

        $response->assertJsonCount(10, 'data')
            ->assertJsonFragment(['current_page' => 1])
            ->assertJsonFragment(['per_page' => 10])
            ->assertJsonFragment(['total' => 11]);
    }
}
