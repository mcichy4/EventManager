<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use Carbon\CarbonImmutable;
use DomainException;

/**
 * Aktualizuje wyłącznie przesłane, dozwolone pola szkicu. Sprawdza wynikowy stan przed zapisem.
 */
class UpdateEvent
{
    public function handle(Event $event, array $data): Event
    {

        if ($event->status !== EventStatus::DRAFT) {
            throw new DomainException('Only draft event can be updated ');
        }

        if (array_key_exists('participant_limit', $data) && $data['participant_limit'] !== null && $data['participant_limit'] <= 0) {
            throw new DomainException('Participant limit must be greater than 0');
        }

        // PATCH może zawierać jedną datę: drugą bierzemy z istniejącego wydarzenia.
        $startsAt = CarbonImmutable::parse($data['starts_at'] ?? $event->starts_at);
        $endsAt = CarbonImmutable::parse($data['ends_at'] ?? $event->ends_at);

        if ($startsAt >= $endsAt) {
            throw new DomainException('The starts at must be a date before ends at.');
        }

        // Obecny klucz z null usuwa termin. Brak klucza zachowuje dotychczasową wartość.
        // Operator ?? nie rozróżniałby tych dwóch przypadków.
        $applicationDeadline = array_key_exists('application_deadline', $data)
            ? $data['application_deadline']
            : $event->application_deadline;

        if ($applicationDeadline !== null) {
            $applicationDeadline = CarbonImmutable::parse($applicationDeadline);

            if ($applicationDeadline > $startsAt) {
                throw new DomainException('Application deadline cannot be after the event starts.');
            }
        }

        // Jawna lista nie pozwala tym mechanizmem zmienić np. statusu lub organizatora.
        $editableFields = [
            'title',
            'description',
            'location',
            'participant_limit',
            'starts_at',
            'ends_at',
            'application_deadline',
        ];

        // Dopiero po sprawdzeniu reguł zmieniamy pola modelu; pominięte pola zostają bez zmian.
        foreach ($editableFields as $field) {
            if (array_key_exists($field, $data)) {
                $event->$field = $data[$field];
            }
        }

        $event->save();

        return $event;
    }
}
