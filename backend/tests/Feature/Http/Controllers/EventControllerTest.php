<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\OrganizerType;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testy tworzenia przez API: poprawne dane, błędy walidacji oraz dostęp do organizatora.
 */
class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tworzy organizatora z domyślnymi wartościami.
     *
     * @return Organizer - zwraca utworzony obiekt organizatora.
     */
    private function createOrganization(): Organizer
    {
        return Organizer::forceCreate([
            'name' => 'Test Organizer',
            'type' => OrganizerType::COMPANY,
        ]);
    }

    /**
     * Tworzy dane wydarzenia z domyślnymi wartościami, które można nadpisać poprzez przekazanie tablicy $overrides.
     *
     * @param  array  $overrides  - tablica z wartościami do nadpisania domyślnych danych wydarzenia.
     * @return array - tablica z danymi wydarzenia gotowa do wysłania w żądaniu POST.
     */
    private function createValidEventData(array $overrides = []): array
    {

        return array_merge([
            'title' => 'Test Event',
            'description' => 'This is a test event.',
            'starts_at' => now()->addDays(10)->toISOString(),
            'ends_at' => now()->addDays(11)->toISOString(),
            'application_deadline' => now()->addDays(6)->toISOString(),
            'location' => 'Test Location',
            'participant_limit' => 100,
        ], $overrides);
    }

    /**
     * Testy dzialania endpointu do tworzenia wydarzen przez API.
     * Idea dzialania: Uzytkownik tworzy wydarzenie dla organizatora, do ktorego nalezy.
     * scenariusz: Najpierw tworzymy uzytkownika($user = User::factory()->create()), nastepnie tworzymy organizatora
     * ($organizer = Organizer::forceCreate([...])) i do tego organizatora przypisujemy uzytkownika ($organizer->users()->attach($user)).
     * Nastepnie uzytkownik loguje sie poprzez $this->actingAs($user) i wysyla POST request do endpointu /api/organizers/{organizer}/events
     * W odpowiedzi spodziewamy sie statusu 201 Created i sprawdzamy, czy w bazie danych istnieje rekord wydarzenia z odpowiednimi danymi.
     */
    public function test_event_can_be_created_via_api(): void
    {
        $user = User::factory()->create();

        $organizer = $this->createOrganization();

        $organizer->users()->attach($user);

        $this->actingAs($user);

        $response = $this->postJson(
            "/api/organizers/{$organizer->id}/events",
            $this->createValidEventData()
        );

        $response->assertCreated();

        $this->assertDatabaseHas('events', [
            'organizer_id' => $organizer->id,
            'title' => 'Test Event',
            'description' => 'This is a test event.',
            'location' => 'Test Location',
            'participant_limit' => 100,
            'status' => EventStatus::DRAFT->value,
        ]);
    }

    /**
     * Testuje, czy można utworzyć wydarzenie bez podania terminu składania wniosków
     * Scenariusz: Tworzymy użytkownika i organizatora, do którego użytkownik należy.
     * Następnie logujemy się jako ten użytkownik i wysyłamy
     * żądanie POST do endpointu /api/organizers/{organizer}/events z danymi wydarzenia,
     * ale bez podania terminu składania wniosków. Oczekujemy, że odpowiedź będzie
     * miała status 201 Created i że w bazie danych zostanie utworzony rekord
     * wydarzenia z wartością null dla pola 'application_deadline'.
     */
    public function test_application_deadline_can_be_null(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganization();
        $organizer->users()->attach($user);
        $this->actingAs($user);

        $data = $this->createValidEventData();
        unset($data['application_deadline']);

        $response = $this->postJson(
            "/api/organizers/{$organizer->id}/events",
            $data
        );

        $response->assertCreated();

        $this->assertDatabaseHas('events', [
            'organizer_id' => $organizer->id,
            'title' => 'Test Event',
            'description' => 'This is a test event.',
            'location' => 'Test Location',
            'participant_limit' => 100,
            'application_deadline' => null,
            'status' => EventStatus::DRAFT->value,
        ]);
    }

    /**
     * Testuje, czy nie można utworzyć wydarzenia z terminem składania wniosków po dacie rozpoczęcia wydarzenia.
     * Scenariusz: Tworzymy użytkownika i organizatora, do którego użytkownik należy.
     * Następnie logujemy się jako ten użytkownik i wysyłamy żądanie POST do endpointu /api/organizers/{organizer}/events
     * z danymi wydarzenia, w których termin składania wniosków jest po dacie rozpoczęcia wydarzenia.
     * Oczekujemy, że odpowiedź będzie miała status 422 Unprocessable Entity
     * i że odpowiedź zawiera message z wyjątku domenowego, a nie errors konkretnego pola.
     */
    public function test_application_deadline_cannot_be_after_event_starts_returns_422(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganization();
        $organizer->users()->attach($user);
        $this->actingAs($user);

        $data = $this->createValidEventData([
            'starts_at' => now()->addDays(8)->toISOString(),
            'application_deadline' => now()->addDays(9)->toISOString(),
        ]);

        $response = $this->postJson(
            "/api/organizers/{$organizer->id}/events",
            $data
        );

        $response
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'Application deadline cannot be after the event starts.',
            ]);
    }

    /**
     * Testuje, czy walidacja działa poprawnie, gdy nie podamy tytułu wydarzenia.
     * Scenariusz: Tworzymy użytkownika i organizatora, do którego użytkownik należy.
     * Następnie logujemy się jako ten użytkownik i wysyłamy żądanie POST do endpointu /api/organizers/{organizer}/events
     * z danymi wydarzenia, ale bez podania tytułu. Oczekujemy, że odpowiedź będzie miała status 422 Unprocessable Entity
     * i że w odpowiedzi znajdzie się informacja o błędzie walidacji dla pola 'title'.
     */
    public function test_title_is_required_to_create_event(): void
    {
        $user = User::factory()->create();

        $organizer = $this->createOrganization();

        $organizer->users()->attach($user);
        $this->actingAs($user);

        $data = $this->createValidEventData();
        unset($data['title']); // nie podajemy tytułu aby sprawdzić, czy walidacja zadziała poprawnie

        $response = $this->postJson(
            "/api/organizers/{$organizer->id}/events",
            $data
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * Testuje, czy użytkownik nie może tworzyć wydarzeń dla organizatora, do którego nie należy.
     * Scenariusz: Tworzymy użytkownika i organizatora, do którego użytkownik nie należy.\
     * Następnie logujemy się jako ten użytkownik i wysyłamy żądanie POST do endpointu /api/organizers/{organizer}/events
     * z danymi wydarzenia.
     */
    public function test_user_cannot_create_event_for_organizer_they_do_not_belong_to(): void
    {
        $user = User::factory()->create();
        $organizer = $this->createOrganization();

        $this->actingAs($user);

        $response = $this->postJson(
            "/api/organizers/{$organizer->id}/events",
            $this->createValidEventData()
        );

        $response->assertForbidden();

        $this->assertDatabaseMissing('events', [
            'organizer_id' => $organizer->id,
            'title' => 'Test Event',
        ]);
    }

    /**
     * Testuje, czy użytkownik nie może tworzyć wydarzeń bez uwierzytelnienia.
     * Scenariusz: Tworzymy organizatora, a następnie wysyłamy żądanie POST do endpointu /api/organizers/{organizer}/events
     * bez uwierzytelnienia. Oczekujemy, że odpowiedź będzie miała status 401 Unauthorized i że w bazie danych nie zostanie utworzony
     * rekord wydarzenia.
     */
    public function test_user_cannot_create_event_without_authentication(): void
    {
        $organizer = $this->createOrganization();

        $response = $this->postJson(
            "/api/organizers/{$organizer->id}/events",
            $this->createValidEventData()
        );

        $response->assertUnauthorized();

        $this->assertDatabaseMissing('events', [
            'organizer_id' => $organizer->id,
            'title' => 'Test Event',
        ]);
    }
}
