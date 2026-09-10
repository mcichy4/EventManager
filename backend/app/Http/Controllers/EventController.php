<?php

namespace App\Http\Controllers;

use App\Actions\Events\CreateEvent;
use App\Data\Events\CreateEventData;
use App\Http\Requests\CreateEventRequest;
use App\Models\Organizer;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function store(CreateEventRequest $request, Organizer $organizer, CreateEvent $action): JsonResponse
    {
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

        return response()->json($event, 201);
    }
}
