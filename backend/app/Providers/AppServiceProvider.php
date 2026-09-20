<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
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
        ResetPassword::createUrlUsing(function ($notifiable, string $token): string {
            $frontendUrl = rtrim((string) config('services.frontend.url'), '/');

            return $frontendUrl.'/reset-password?'.http_build_query([
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        });
    }
}
