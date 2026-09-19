<?php

namespace App\Http\Controllers;

use App\Actions\Events\AcceptEventApplication;
use App\Actions\Events\ApplyToEvent;
use App\Actions\Events\CancelEvent;
use App\Actions\Events\CancelEventApplication;
use App\Actions\Events\CreateEvent;
use App\Actions\Events\DeleteEvent;
use App\Actions\Events\PublishEvent;
use App\Actions\Events\RejectEventApplication;
use App\Actions\Events\UpdateEvent;
use App\Data\Events\CreateEventData;
use App\Enums\EventStatus;
use App\Http\Requests\CreateEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Models\EventApplication;
use App\Models\Organizer;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Łączy HTTP z akcjami: odbiera zwalidowane dane, sprawdza uprawnienia i zwraca JSON.
 * Laravel wstrzykuje akcje oraz modele rozpoznane na podstawie parametrów trasy.
 */
class EventController extends Controller
{
    public function store(CreateEventRequest $request, Organizer $organizer, CreateEvent $action): JsonResponse
    {
        // Do DTO trafiają tylko zwalidowane pola; daty z HTTP zamieniamy na obiekty.
        $data = new CreateEventData(
            title: $request->validated('title'),
            description: $request->validated('description'),
            startsAt: CarbonImmutable::parse($request->validated('starts_at')),
            endsAt: CarbonImmutable::parse($request->validated('ends_at')),
            applicationDeadline: $request->validated('application_deadline')
                ? CarbonImmutable::parse($request->validated('application_deadline'))
                : null,
            location: $request->validated('location'),
            participantLimit: $request->validated('participant_limit'),
        );

        $event = $action->execute($organizer, $data);

        // 201 oznacza utworzenie nowego zasobu, a nie tylko pomyślne wykonanie żądania.
        return response()->json($event, 201);
    }

    public function update(
        UpdateEventRequest $request,
        Event $event,
        UpdateEvent $updateEvent
    ): JsonResponse {
        // Odmowa policy przerywa żądanie kodem 403 przed uruchomieniem akcji.
        Gate::authorize('update', $event);

        $event = $updateEvent->handle($event, $request->validated());

        return response()->json($event);
    }

    public function publish(Event $event, PublishEvent $publishEvent): JsonResponse
    {
        // Policy sprawdza członkostwo, a akcja poniżej sprawdzi status wydarzenia.
        Gate::authorize('publish', $event);

        $event = $publishEvent->execute($event);

        return response()->json($event);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Event::query()
            ->where('status', EventStatus::PUBLISHED->value);

        $search = $request->query('search');
        if (is_string($search) && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        $events = $query
            ->orderBy('id')
            ->paginate(10)
            ->appends([
                'search' => $search,
            ]);

        return response()->json($events);
    }

    public function show(Event $event): JsonResponse
    {
        if ($event->status !== EventStatus::PUBLISHED) {
            abort(404, 'Event not found');
        }

        return response()->json($event);
    }

    public function apply(Request $request, Event $event, ApplyToEvent $applyToEvent): JsonResponse
    {
        Gate::authorize('apply', $event);

        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthorized');
        }

        $result = $applyToEvent->execute($user, $event);

        return response()->json($result, 201);

    }

    public function cancelApplication(EventApplication $eventApplication, CancelEventApplication $cancelEventApplication): JsonResponse
    {
        Gate::authorize('cancel', $eventApplication);

        $cancelEventApplication->execute($eventApplication);

        return response()->json(['message' => 'Application canceled successfully.']);
    }

    public function acceptApplication(EventApplication $eventApplication, AcceptEventApplication $acceptEventApplication): JsonResponse
    {
        Gate::authorize('accept', $eventApplication);

        $acceptEventApplication->execute($eventApplication);

        return response()->json(['message' => 'Application accepted successfully.']);
    }

    public function rejectApplication(EventApplication $eventApplication, RejectEventApplication $rejectEventApplication): JsonResponse
    {
        Gate::authorize('reject', $eventApplication);

        $rejectEventApplication->execute($eventApplication);

        return response()->json(['message' => 'Application rejected successfully.']);
    }

    public function myApplications(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthorized');
        }

        $applications = EventApplication::where('user_id', $user->id)
            ->with('event.organizer')
            ->latest()
            ->get();

        return response()->json($applications);
    }

    public function cancelEvent(Event $event, CancelEvent $cancelEvent): JsonResponse
    {
        Gate::authorize('cancel', $event);

        $cancelEvent->handle($event);

        return response()->json($event);
    }

    public function destroy(Event $event, DeleteEvent $deleteEvent): JsonResponse
    {
        Gate::authorize('delete', $event);

        $deleteEvent->handle($event);

        return response()->json(null, 204);
    }

    public function listApplications(Event $event): JsonResponse
    {
        Gate::authorize('listApplications', $event);

        $applications = $event->applications()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->through(function (EventApplication $application) {
                return [
                    'id' => $application->id,
                    'user_id' => $application->user_id,
                    'name' => $application->user->name,
                    'status' => $application->status,
                ];
            });

        return response()->json($applications);
    }
}
