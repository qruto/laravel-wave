<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

it('completely installs all required assets for broadcasting in default scenario', function () {
    File::shouldReceive('copy')
        ->once()
        ->withArgs(fn($path, $target) => str_ends_with($path, 'broadcasting-routes.stub') && str_ends_with($target, 'routes/channels.php'));

    File::shouldReceive('copy')
        ->once()
        ->withArgs(fn($path, $target) => str_ends_with($path, 'echo-js.stub') && str_ends_with($target, 'js/echo.js'));

    File::shouldReceive('missing')
        ->once()
        ->withArgs([app()->environmentFile()])
        ->andReturn(false);

    File::shouldReceive('get')
        ->once()
        ->withArgs([app()->environmentFile()])
        ->andReturn('BROADCAST_CONNECTION=log');

    File::shouldReceive('put')
        ->once()
        ->withArgs([app()->environmentFile(), 'BROADCAST_CONNECTION=redis']);

    Process::fake([
        'npm install --save-dev laravel-echo laravel-wave && npm run build' => Process::result()
    ]);

    $this->artisan('install:broadcasting')
        ->expectsConfirmation('Would you like to enable the Redis broadcasting driver for Wave?', 'yes')
        ->expectsConfirmation('Would you like to install and build the Node dependencies required for broadcasting?', 'yes')
        ->expectsOutputToContain('Installing and building Node dependencies.')
        ->expectsOutputToContain('Node dependencies installed successfully.')
        ->expectsConfirmation('Would you like to publish the Wave configuration file?')
        ->assertExitCode(0);

    $this->assertFileExists($this->app->configPath('broadcasting.php'));
    unlink($this->app->configPath('broadcasting.php'));
});
