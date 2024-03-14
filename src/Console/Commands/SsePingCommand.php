<?php

namespace Qruto\Wave\Console\Commands;

use Illuminate\Console\Command;
use Qruto\Wave\Events\SsePingEvent;

class SsePingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sse:ping {--interval= : interval in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ping server sent event connections';

    public function handle()
    {
        $interval = $this->option('interval');

        if ($interval) {
            while (true) {
                event(new SsePingEvent());

                $this->components->twoColumnDetail('<fg=gray>'.now().'</> SSE Wave Connections', '<fg=green;options=bold>PINGED</>');

                sleep($interval);
            }
        } else {
            $this->components->twoColumnDetail('<fg=gray>'.now().'</> SSE Wave Connections', '<fg=green;options=bold>PINGED</>');

            event(new SsePingEvent());
        }
    }
}
