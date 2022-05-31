<?php

namespace Furio\LaravelWave;

use Furio\LaravelWave\Commands\LaravelWaveCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelWaveServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-wave')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel-wave_table')
            ->hasCommand(LaravelWaveCommand::class);
    }
}
