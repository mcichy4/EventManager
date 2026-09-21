<?php

namespace App\Enums;

enum EventCategory: string
{
    case OTHER = 'other';
    case WORKSHOPS = 'workshops';
    case SPORT = 'sport';
    case MUSIC = 'music';
    case NETWORKING = 'networking';
    case EDUCATION = 'education';
}
