<?php

namespace App\Models;

use App\Enums\EventApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model zgłoszenia łączącego użytkownika z wydarzeniem. Status opisuje decyzję dotyczącą uczestnictwa.
 */
class EventApplication extends Model
{
    // Pola do masowego przypisania. Przy tworzeniu przez relację event_id ustala relacja.
    protected $fillable = [
        'user_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => EventApplicationStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
