<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\OrganizerMemberRole;
use App\Enums\OrganizerType;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
            ]
        );

        $organizer = Organizer::query()->firstOrCreate(
            ['name' => 'EventManager Studio'],
            [
                'type' => OrganizerType::COMPANY,
                'description' => 'Przykładowy organizator wydarzeń do prezentacji aplikacji.',
            ]
        );

        $organizer->users()->syncWithoutDetaching([
            $user->id => ['role' => OrganizerMemberRole::OWNER],
        ]);

        Event::query()->updateOrCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Warsztaty fotografii miejskiej',
        ], [
            'organizer_id' => $organizer->id,
            'description' => 'Praktyczne warsztaty dla osób, które chcą lepiej fotografować miasto.',
            'category' => 'workshops',
            'starts_at' => now()->addDays(7)->setTime(10, 0),
            'ends_at' => now()->addDays(7)->setTime(15, 0),
            'application_deadline' => now()->addDays(5),
            'location' => 'Dom Kultury Śródmieście',
            'address' => 'ul. Marszałkowska 10, Warszawa',
            'participant_limit' => 20,
            'status' => EventStatus::PUBLISHED,
        ]);

        Event::query()->updateOrCreate([
            'organizer_id' => $organizer->id,
            'title' => 'Wieczór networkingowy',
        ], [
            'organizer_id' => $organizer->id,
            'description' => 'Spotkanie lokalnej społeczności technologicznej.',
            'category' => 'networking',
            'starts_at' => now()->addDays(14)->setTime(18, 0),
            'ends_at' => now()->addDays(14)->setTime(21, 0),
            'location' => 'Centrum Przedsiębiorczości',
            'address' => 'ul. Prosta 20, Warszawa',
            'participant_limit' => 50,
            'status' => EventStatus::PUBLISHED,
        ]);
    }
}
