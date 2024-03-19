<?php

namespace Qruto\Wave\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

use function file_get_contents;
use function Laravel\Prompts\confirm;

class BroadcastingInstallCommand extends \Illuminate\Foundation\Console\BroadcastingInstallCommand
{
    /** {@inheritdoc} */
    public function handle(): void
    {
        $this->call('config:publish', ['name' => 'broadcasting']);

        // Install channel routes file...
        if (file_exists($broadcastingRoutesPath = $this->laravel->basePath('routes/channels.php')) &&
            ! $this->option('force')) {
            $this->components->error('Broadcasting routes file already exists.');
        } else {
            $this->components->info("Published 'channels' route file.");

            $relativeBroadcastingRoutesStub = 'laravel/framework/src/Illuminate/Foundation/Console/stubs/broadcasting-routes.stub';

            if (file_exists(__DIR__.'/../../../../'.$relativeBroadcastingRoutesStub)) {
                File::copy(__DIR__.'/../../../stubs/broadcasting-routes.stub', $broadcastingRoutesPath);
            } else {
                File::copy(__DIR__.'/../../../vendor/'.$relativeBroadcastingRoutesStub, $broadcastingRoutesPath);
            }
        }

        $this->uncommentChannelsRoutesFile();
        $this->enableBroadcastServiceProvider();

        // Install bootstrapping...
        if (! file_exists($echoScriptPath = $this->laravel->resourcePath('js/echo.js'))) {
            File::copy(__DIR__.'/../../../stubs/echo-js.stub', $echoScriptPath);
        }

        if (file_exists($bootstrapScriptPath = $this->laravel->resourcePath('js/bootstrap.js'))) {
            $bootstrapScript = file_get_contents(
                $bootstrapScriptPath
            );

            if (! str_contains($bootstrapScript, './echo')) {
                File::append(
                    $bootstrapScriptPath,
                    PHP_EOL.file_get_contents(__DIR__.'/../../../stubs/echo-bootstrap-js.stub')
                );
            }
        }

        $this->updateBroadcastingDriver();

        $this->installNodeDependencies();

        $this->publishConfiguration();
    }

    /** {@inheritdoc} */
    protected function installNodeDependencies()
    {
        if (! confirm('Would you like to install and build the Node dependencies required for broadcasting?', default: true)) {
            return;
        }

        $this->components->info('Installing and building Node dependencies.');

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $commands = [
                'pnpm add --save-dev laravel-echo laravel-wave',
                'pnpm run build',
            ];
        } elseif (file_exists(base_path('yarn.lock'))) {
            $commands = [
                'yarn add --dev laravel-echo laravel-wave',
                'yarn run build',
            ];
        } else {
            $commands = [
                'npm install --save-dev laravel-echo laravel-wave',
                'npm run build',
            ];
        }

        $command = Process::command(implode(' && ', $commands))
            ->path(base_path());

        if (! windows_os()) {
            $command->tty(true);
        }

        if ($command->run()->failed()) {
            $this->components->warn("Node dependency installation failed. Please run the following commands manually: \n\n".implode(' && ', $commands));
        } else {
            $this->components->info('Node dependencies installed successfully.');
        }
    }

    /**
     * Update the configured broadcasting driver.
     */
    protected function updateBroadcastingDriver(): void
    {
        $enable = confirm(
            'Would you like to enable the Redis broadcasting driver for Wave?',
            default: true,
        );

        if (! $enable || File::missing($env = app()->environmentFile())) {
            return;
        }

        File::put(
            $env,
            Str::of(File::get($env))->replaceMatches('/(BROADCAST_(?:DRIVER|CONNECTION))=\w*/', function (array $matches) {
                return $matches[1].'=redis';
            })->value()
        );
    }

    protected function publishConfiguration(): void
    {
        if (! confirm('Would you like to publish the Wave configuration file?', default: false, hint: 'This will allow you to configure the SSE connection.')) {
            return;
        }

        $this->callSilently('vendor:publish', [
            '--provider' => 'Qruto\Wave\WaveServiceProvider',
            '--tag' => 'wave-config',
        ]);
    }
}
