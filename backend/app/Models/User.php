<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Konto użytkownika: obsługuje uwierzytelnianie, członkostwo u organizatorów i zgłoszenia.
 * Fillable dopuszcza masowe przypisanie pól, a Hidden ukrywa poufne pola w serializacji.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Zamienia datę weryfikacji na obiekt daty i hashuje przypisywane hasło.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Ta relacja jest używana przez policy do sprawdzania członkostwa użytkownika.
    public function organizers(): BelongsToMany
    {
        return $this->belongsToMany(Organizer::class)->withTimestamps();
    }

    public function eventApplications(): HasMany
    {
        return $this->hasMany(EventApplication::class);
    }
}
