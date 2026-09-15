<?php

namespace App\Models;

use App\Enums\OrganizerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Organizator posiada wydarzenia i wielu członków. Członkostwo jest podstawą uprawnień do zarządzania.
 */
class Organizer extends Model
{
    // Enum typu organizatora zastępuje ręczne porównywanie napisów w kodzie.

    protected function casts(): array
    {
        return [
            'type' => OrganizerType::class,
        ];
    }

    // Powiązania są w tabeli organizer_user; withTimestamps zapisuje daty członkostwa w pivot.
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
