<?php

namespace Furio\LaravelWave\Commands;

use Illuminate\Console\Command;

class LaravelWaveCommand extends Command
{
    public $signature = 'laravel-wave';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
