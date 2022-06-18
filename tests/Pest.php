<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ReflectsClosures;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;
use Qruto\LaravelWave\Tests\RedisConnectionMock;
use Qruto\LaravelWave\Tests\Support\User;

use Qruto\LaravelWave\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

uses()->beforeEach(function () {
    $this->instance('redis', new RedisConnectionMock());
    User::factory()->create();
    $this->actingAs(User::first());
})->in(__DIR__);

function waveConnection()
{
    return new class () {
        use ReflectsClosures;

        private $response;

        /** @var \Illuminate\Support\Collection */
        private $sentEvents;

        public function __construct()
        {
            $this->response = test()->get('/wave');
        }

        public function id()
        {
            return $this->getSentEvents()['connected']['data'];
        }

        public function dispatched($event, $callback = null)
        {
            if (! $this->hasDispatched($event)) {
                return collect();
            }

            $callback = $callback ?: fn () => true;

            return collect($this->getSentEvents()[$event])->filter(
                fn ($arguments) => $callback($arguments['data'])
            );
        }

        public function assertConnected()
        {
            assertTrue(
                $this->dispatched('connected')->count() > 0,
                "Connection hasn't been established"
            );
        }

        public function assertEventReceived($event, $callback = null)
        {
            if ($event instanceof Closure) {
                [$event, $callback] = [$this->firstClosureParameterType($event), $event];
            }

            $event = (new $event())->broadcastOn()->name.'.'.$event;

            if (is_int($callback)) {
                return $this->assertDispatchedTimes($event, $callback);
            }

            assertTrue(
                $this->dispatched($event, $callback)->count() > 0,
                "The expected [{$event}] event was not dispatched."
            );
        }

        /**
         * Assert if an event was dispatched a number of times.
         *
         * @param  string  $event
         * @param  int  $times
         * @return void
         */
        public function assertDispatchedTimes($event, $times = 1)
        {
            $count = $this->dispatched($event)->count();

            assertSame(
                $times,
                $count,
                "The expected [{$event}] event was dispatched {$count} times instead of {$times} times."
            );
        }

        /**
         * Determine if the given event has been dispatched.
         *
         * @param  string  $event
         * @return bool
         */
        public function hasDispatched($event)
        {
            return isset($this->getSentEvents()[$event]) && ! empty($this->getSentEvents()[$event]);
        }

        public function getSentEvents()
        {
            if (! $this->sentEvents) {
                $rawEvents = array_filter(explode("\n\n\n", $this->response->streamedContent()));
                $this->sentEvents = Collection::make($rawEvents)->map(function ($event) {
                    $rows = explode("\n", $event);
                    $data = Str::after($rows[1], 'data: ');

                    if (Str::startsWith($data, '{')) {
                        $data = json_decode($data, true);
                    }

                    return [
                        'event' => Str::after($rows[0], 'event: '),
                        'data' => $data,
                    ];
                })->mapToGroups(fn ($item) => [$item['event'] => ['data' => $item['data']]]);
            }

            return $this->sentEvents;
        }
    };
}
