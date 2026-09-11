<?php

namespace App\Data\Events;

use Carbon\CarbonImmutable;

/**
 * DTO przenosi typowane dane z kontrolera do akcji, bez zależności od żądania HTTP.
 * Znak ? dopuszcza null; CarbonImmutable pozwala operować na datach bez zmieniania pierwotnej instancji.
 */
final class CreateEventData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public ?CarbonImmutable $applicationDeadline,
        public string $location,
        public ?int $participantLimit
    ) {}
}
