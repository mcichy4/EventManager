<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return true;
        $organizer = $this->route('organizer');

        return
            $this->user() &&
            $this->user()
                ->organizers()
                ->whereKey($organizer->id)
                ->exists();
    }

    /**
     * Get the validation rules that apply to the request.
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
