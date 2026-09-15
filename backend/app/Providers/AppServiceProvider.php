<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Miejsce rejestrowania usług i wspólnej konfiguracji aplikacji. Obecnie nie dodaje własnego zachowania.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Miejsce na rejestrację zależności w kontenerze usług.
     */
    public function register(): void
    {
        //
    }

    /**
     * Miejsce na konfigurację wykonywaną po rejestracji providerów.
     */
    public function boot(): void
    {
        //
    }
}
