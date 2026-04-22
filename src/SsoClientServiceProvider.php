<?php

namespace MobtakerSystem\SsoClient;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use MobtakerSystem\SsoClient\Commands\SsoClientCommand;
use MobtakerSystem\SsoClient\Providers\MobtakerSsoProvider;
use Laravel\Socialite\Facades\Socialite;

class SsoClientServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('sso-client')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_sso_users_table')
            ->hasCommand(SsoClientCommand::class);
    }

    public function registeringPackage(): void
    {
        // Register SsoClient as singleton
        $this->app->singleton(\MobtakerSystem\SsoClient\SsoClient::class, function () {
            return new \MobtakerSystem\SsoClient\SsoClient();
        });

        // Register custom Socialite driver
        Socialite::extend('mobtaker-sso', function ($app) {
            $config = config('sso-client.provider');

            return Socialite::buildProvider(
                MobtakerSsoProvider::class,
                [
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'redirect_uri' => $config['redirect_uri'],
                ]
            );
        });
    }

    public function bootingPackage(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/sso-client.php' => config_path('sso-client.php'),
        ], 'sso-client-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'sso-client-migrations');
    }
}
