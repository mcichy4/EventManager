<?php

namespace App\Enums;

/**
 * Rozróżnia organizatora indywidualnego i firmę; nie definiuje ról ani uprawnień użytkowników.
 */
enum OrganizerType: string
{
    case INDIVIDUAL = 'individual';
    case COMPANY = 'company';
}
