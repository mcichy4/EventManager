<?php

namespace App\Enums;

/**
 * Dozwolone statusy wydarzenia. Wartości tekstowe są zapisywane w bazie; przejść między nimi pilnują akcje.
 */
enum EventStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case CANCELLED = 'cancelled';
}
