<?php

namespace App\Actions\Organizers;

use App\Models\Organizer;
use App\Models\User;
use App\Enums\OrganizerMemberRole;
use Illuminate\Support\Facades\DB;


class CreateOrganizer
{
    public function execute(User $user,string $name, string $type, ?string $description = null): Organizer
    {
        return DB::transaction(function() use ($user,$name, $type, $description) {
            $organizer = Organizer::forceCreate([
                'name' => $name,
                'type' => $type,
                'description' => $description,
            ]);
            $organizer->users()->attach($user, [
                'role' => OrganizerMemberRole::OWNER->value,
            ]);
            return $organizer;
        });
    }

}
