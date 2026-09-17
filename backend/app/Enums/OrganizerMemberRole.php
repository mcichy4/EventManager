<?php

namespace App\Enums;

enum OrganizerMemberRole: string
{
    case OWNER = 'owner';
    case MEMBER = 'member';
}
