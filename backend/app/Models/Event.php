<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    //
    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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

    public function isFinished(): bool
    {
        return $this->ends_at->isPast();
    }
}
