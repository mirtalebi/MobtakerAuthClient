<?php

namespace MobtakerSystem\SsoClient\Logs;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use MobtakerSystem\SsoClient\Events\UserAuthenticated;
use MobtakerSystem\SsoClient\Events\UserSynced;

class SsoEventServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Boot the application services.
     */
    public function boot(): void
    {
        // Listen to user authentication event
        Event::listen(UserAuthenticated::class, function (UserAuthenticated $event) {
            // Log user authentication
            \Log::info('User authenticated via SSO', [
                'user_id' => $event->user->id ?? null,
                'mobile' => $event->user->mobile ?? null,
                'sso_id' => $event->socialiteUser->getId() ?? null,
            ]);

            // Dispatch custom logic here
            // Example: Update user last login, send mobile, etc.
        });

        // Listen to user sync event
        Event::listen(UserSynced::class, function (UserSynced $event) {
            // Log user sync
            \Log::info('User synced from SSO', [
                'user_id' => $event->user->id ?? null,
                'mobile' => $event->user->mobile ?? null,
            ]);

            // Dispatch custom logic here
            // Example: Update permissions, sync roles, etc.
        });
    }
}
