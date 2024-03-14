<?php

namespace Qruto\Wave\Console\Commands;

use Qruto\Wave\WaveServiceProvider;

use function file_get_contents;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function str_ends_with;

class ServeCommand extends \Illuminate\Foundation\Console\ServeCommand
{
    /**
     * Modifies the default `handle` method to adjust the `PHP_CLI_SERVER_WORKERS` value.
     * Increases concurrent connections to enable real-time Server-Sent Events (SSE) setup.
     * {inheritdoc}
     */
    public function handle()
    {
        $workersAmount = env('PHP_CLI_SERVER_WORKERS', 1);

        if ($workersAmount === 1 && $this->getLaravel()->getProviders(WaveServiceProvider::class) !== []) {
            $workersAmount = (int) select(
                label: 'Looks like you are using Server-sent Events (SSE) for broadcasting. Set the preferable amount of workers.',
                options: ['leave 1 (default)', 10, 20],
                default: 10,
                hint: 'The number of workers determines the maximum number of concurrent connections.',
            );

            $_ENV['PHP_CLI_SERVER_WORKERS'] = $workersAmount;
            putenv("PHP_CLI_SERVER_WORKERS={$workersAmount}");

            if (confirm('Do you want to save preferable amount to the .env file?', false)) {
                $file = base_path('.env');

                if (file_exists($file)) {
                    file_put_contents($file,
                        (str_ends_with(file_get_contents($file), PHP_EOL) ? '' : PHP_EOL).
                        PHP_EOL."PHP_CLI_SERVER_WORKERS=$workersAmount".PHP_EOL, FILE_APPEND);
                }
            }
        }

        return parent::handle();
    }
}
