<?php

namespace App\Data\Events;

use Carbon\CarbonImmutable;

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
