<?php

namespace App\Http\Requests;

use App\Enums\EventCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Waliduje dane częściowej aktualizacji. Uprawnienia sprawdza Gate w kontrolerze.
 * sometimes pozwala pominąć pole, nullable pozwala jawnie przesłać null.
 * Kolejność dat sprawdza akcja, łącząc nowe wartości z zapisanymi w bazie.
 */
class UpdateEventRequest extends FormRequest
{
    /**
     * Nie blokuje żądania tutaj: właściwą policy wywołuje kontroler przez Gate.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sprawdza tylko przesłane pola; zależności między datami należą do akcji.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'category' => ['sometimes', 'string', Rule::enum(EventCategory::class)],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date'],
            'application_deadline' => ['sometimes', 'nullable', 'date'],
            'participant_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'location' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
