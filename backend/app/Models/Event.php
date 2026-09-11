<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model tabeli events: przechowuje dane wydarzenia i łączy je z organizatorem oraz zgłoszeniami.
 */
class Event extends Model
{
    // Casty zamieniają wartości z bazy na enum statusu i obiekty dat.
    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'application_deadline' => 'datetime',
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(EventApplication::class);
    }

    // Zakończenie wynika z daty końca, niezależnie od statusu publikacji/anulowania.
    public function isFinished(): bool
    {
        return $this->ends_at->isPast();
    }
}
