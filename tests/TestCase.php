<?php

namespace Qruto\LaravelWave\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Qruto\LaravelWave\LaravelWaveServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Qruto\\LaravelWave\\Tests\\Support\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelWaveServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'base64:LjpSHzPr1BBeuRWrlUcN2n2OWZ36o8+VpTLZdHcdG7Q=');

        $app['config']->set('database.default', 'testing');

        $app['config']->set('cache.default', 'array');

        $app['config']->set('broadcasting.default', 'redis');

        $app['config']->set('auth.providers.users.model', Support\User::class);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');

            $table->timestamps();
        });
    }
}
