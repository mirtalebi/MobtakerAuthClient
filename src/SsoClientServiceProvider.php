<?php

namespace Mobtaker System\SsoClient;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Mobtaker System\SsoClient\Commands\SsoClientCommand;

class SsoClientServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('sso-client')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_sso_client_table')
            ->hasCommand(SsoClientCommand::class);
    }
}
