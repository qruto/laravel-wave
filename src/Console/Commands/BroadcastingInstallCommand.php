<?php

namespace Qruto\Wave\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Qruto\Wave\WaveServiceProvider;
use Symfony\Component\Process\Process as SymfonyProcess;

use function file_get_contents;
use function Laravel\Prompts\confirm;

class BroadcastingInstallCommand extends \Illuminate\Foundation\Console\BroadcastingInstallCommand
{
    /** {@inheritdoc} */
    public function handle(): int
    {
        $this->output->write($this->title());

        $enableRedisBroadcastingDriver = confirm(
            'Would you like to enable the Redis broadcasting driver in .env?',
            default: true,
        );

        $installNodeDependencies = confirm(
            'Install and build the Node dependencies required for broadcasting?',
            default: true
        );

        $publishConfiguration = confirm(
            'Publish the Wave configuration file?',
            default: false,
        );

        $this->askToStarRepository();

        $this->call('config:publish', ['name' => 'broadcasting']);

        // Install channel routes file...
        if (file_exists($broadcastingRoutesPath = $this->laravel->basePath('routes/channels.php')) &&
            ! $this->option('force')) {
            $this->components->error('Broadcasting routes file already exists.');
        } else {
            $this->components->info("Published 'channels' route file.");

            $relativeBroadcastingRoutesStub = 'laravel/framework/src/Illuminate/Foundation/Console/stubs/broadcasting-routes.stub';

            if (file_exists(__DIR__.'/../../../../../'.$relativeBroadcastingRoutesStub)) {
                File::copy(
                    __DIR__.'/../../../../../'.$relativeBroadcastingRoutesStub,
                    $broadcastingRoutesPath
                );
            } else {
                File::copy(__DIR__.'/../../../vendor/'.$relativeBroadcastingRoutesStub,
                    $broadcastingRoutesPath);
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

        if ($enableRedisBroadcastingDriver) {
            $this->updateBroadcastingDriver();
        }

        if ($installNodeDependencies) {
            $this->installNodeDependencies();
        }

        if ($publishConfiguration) {
            $this->publishConfiguration();
        }

        return Command::SUCCESS;
    }

    /** {@inheritdoc} */
    protected function installNodeDependencies()
    {
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

        if (! windows_os() && SymfonyProcess::isTtySupported()) {
            $command->tty();
        }

        if ($command->run()->failed()) {
            $this->components->warn(
                "Node dependency installation failed. Please run the following commands manually: \n\n"
                .implode(' && ', $commands)
            );
        } else {
            $this->components->info('Node dependencies installed successfully.');
        }
    }

    /**
     * Update the configured broadcasting driver.
     */
    protected function updateBroadcastingDriver(): void
    {
        if (File::missing($env = app()->environmentFile())) {
            return;
        }

        File::put(
            $env,
            Str::of(File::get($env))->replaceMatches(
                '/(BROADCAST_(?:DRIVER|CONNECTION))=\w*/',
                fn (array $matches) => $matches[1].'=redis'
            )->value()
        );
    }

    protected function publishConfiguration(): void
    {
        $this->callSilently('vendor:publish', [
            '--provider' => WaveServiceProvider::class,
            '--tag' => 'wave-config',
        ]);
    }

    protected function askToStarRepository()
    {
        if (! confirm(
            'Star Wave repo on GitHub during installation?',
            default: ! $this->option('no-interaction'),
            hint: 'Your yellow star contributes to the package development ⭐'
        )) {
            return;
        }

        $repoUrl = 'https://github.com/qruto/laravel-wave';

        if (PHP_OS_FAMILY === 'Darwin') {
            exec("open {$repoUrl}");
        }
        if (PHP_OS_FAMILY === 'Windows') {
            exec("start {$repoUrl}");
        }
        if (PHP_OS_FAMILY === 'Linux') {
            exec("xdg-open {$repoUrl}");
        }
    }

    private function title()
    {
        return PHP_EOL.<<<'TITLE'
        <fg=blue>
             ____                      _               _   _
            | __ ) _ __ ___   __ _  __| | ___ __ _ ___| |_(_)_ __   __ _
            |  _ \| '__/ _ \ / _` |/ _` |/ __/ _` / __| __| | '_ \ / _` |
            | |_) | | | (_) | (_| | (_| | (_| (_| \__ \ |_| | | | | (_| |
            |____/|_| _\___/_\__,_|\__,_|\___\__,_|___/\__|_|_| |_|\__, |
            __      _(_) |_| |__   \ \      / /_ ___   _____       |___/
            \ \ /\ / / | __| '_ \   \ \ /\ / / _` \ \ / / _ \
             \ V  V /| | |_| | | |   \ V  V / (_| |\ V /  __/
              \_/\_/ |_|\__|_| |_|    \_/\_/ \__,_| \_/ \___|
        </>
        TITLE.PHP_EOL.PHP_EOL;
    }
}
