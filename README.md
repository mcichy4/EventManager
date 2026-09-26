# EventManager

EventManager to aplikacja webowa do odkrywania wydarzeń i obsługi całego procesu zgłoszeń — od publikacji wydarzenia przez organizatora po decyzję o przyjęciu uczestnika.

Projekt jest rozwijany jako portfolio full-stack z backendem Laravel oraz frontendem React.

## Funkcje

### Uczestnik

- rejestracja, logowanie i wylogowanie przez Laravel Sanctum;
- katalog opublikowanych wydarzeń z wyszukiwaniem;
- szczegóły wydarzenia, limit miejsc i termin zgłoszeń;
- zgłoszenie na wydarzenie oraz wycofanie własnego zgłoszenia;
- lista własnych zgłoszeń i ich statusów.

### Organizator

- tworzenie organizacji;
- tworzenie, edycja, publikacja, anulowanie i usuwanie wydarzeń;
- lista wydarzeń organizacji wraz ze statusem i liczbą zgłoszeń;
- akceptowanie oraz odrzucanie oczekujących zgłoszeń.

## Stack

- PHP 8.4, Laravel 13 i Laravel Sanctum;
- PostgreSQL 17;
- React 19, React Router 7, Axios i Vite 8;
- Docker Compose oraz Nginx;
- PHPUnit i Oxlint.

## Uruchomienie lokalne

Wymagane są Docker lub OrbStack oraz Docker Compose.

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec php composer install
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --seed
docker compose exec node npm install
```

Aplikacja jest dostępna pod adresami:

- frontend: `http://localhost:5173`;
- API Laravel: `http://localhost:8000/api`.

Przykładowe konto po seedzie:

```text
email: test@example.com
hasło: password
```

## Testy i jakość

```bash
docker compose exec php php artisan test --compact
docker compose exec node npm run lint
docker compose exec node npm run build
```

## Przykładowy workflow

1. Organizator tworzy organizację i szkic wydarzenia.
2. Szkic jest publikowany i pojawia się w katalogu publicznym.
3. Uczestnik zakłada konto i wysyła zgłoszenie.
4. Organizator akceptuje albo odrzuca zgłoszenie.
5. Uczestnik widzi bieżący status w sekcji „Moje zgłoszenia”.

## Roadmapa

- zarządzanie członkami organizacji;
- reset hasła w interfejsie;
- paginacja i dodatkowe filtry katalogu;
- testy komponentów i end-to-end dla frontendu;
- wersja PWA.

## Zrzuty ekranu

Po uruchomieniu seedera warto dodać do tego README zrzuty ekranu katalogu wydarzeń, panelu organizatora i zarządzania zgłoszeniami. Pozwala to szybko pokazać kluczowe ścieżki projektu na GitHubie oraz LinkedInie.
