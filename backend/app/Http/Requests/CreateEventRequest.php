<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Sprawdza członkostwo u organizatora oraz format danych tworzonego wydarzenia.
 * Zależności między datami sprawdza akcja CreateEvent.
 */
class CreateEventRequest extends FormRequest
{
    /**
     * Tworzyć wydarzenie może członek organizatora wskazanego w trasie.
     */
    public function authorize(): bool
    {
        // Route model binding udostępnia tutaj obiekt organizatora, nie samo ID.
        $organizer = $this->route('organizer');

        return
            $this->user() &&
            $this->user()
                ->organizers()
                ->whereKey($organizer->id)
                ->exists();
    }

    /**
     * Sprawdza wymagane pola i ich typy przed wywołaniem kontrolera.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
            'application_deadline' => ['nullable', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'participant_limit' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
