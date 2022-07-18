<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ReflectsClosures;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;
use Qruto\LaravelWave\Tests\RedisConnectionMock;
use Qruto\LaravelWave\Tests\Support\User;
use Qruto\LaravelWave\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

uses()->beforeEach(function () {
    $redisMock = new RedisConnectionMock();
    $this->instance('redis', $redisMock);
    $redisMock->flushdb();
    $redisMock->flushEventsQueue();

    Broadcast::channel('private-channel', fn () => true);
    Broadcast::channel('presence-channel', fn () => true);

    $this->user = User::factory()->create();

    $this->actingAs($this->user);
})->in(__DIR__);

function waveConnection(Authenticatable $user = null)
{
    return new class($user)
    {
        use ReflectsClosures;

        public $response;

        /** @var \Illuminate\Support\Collection */
        private $sentEvents;

        public function __construct(public Authenticatable|null $user = null)
        {
            $test = test();

            if ($user) {
                $test = $test->actingAs($user);
            }

            $this->response = $test->get(route('wave.connection'));
        }

        public function id()
        {
            return $this->response->headers->get('X-Socket-Id');
        }

        public function received($event, $callback = null)
        {
            if (! $this->hasReceived($event)) {
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
                $this->received('connected')->count() > 0,
                "Connection hasn't been established"
            );
        }

        public function assertEventReceived($event, $callback = null)
        {
            if ($event instanceof Closure) {
                [$event, $callback] = [$this->firstClosureParameterType($event), $event];
            }

            if (class_exists($event)) {
                $event = (new $event())->broadcastOn()->name.'.'.$event;
            }

            if (is_int($callback)) {
                return $this->assertReceivedTimes($event, $callback);
            }

            assertTrue(
                $this->received($event, $callback)->count() > 0,
                "The expected [{$event}] event was not received."
            );
        }

        public function assertEventNotReceived($event, $callback = null)
        {
            if ($event instanceof Closure) {
                [$event, $callback] = [$this->firstClosureParameterType($event), $event];
            }

            assertCount(
                0,
                $this->received($event, $callback),
                "The unexpected [{$event}] event was received."
            );
        }

        /**
         * Assert if an event was received a number of times.
         *
         * @param  string  $event
         * @param  int  $times
         * @return void
         */
        public function assertReceivedTimes($event, $times = 1)
        {
            $count = $this->received($event)->count();

            assertSame(
                $times,
                $count,
                "The expected [{$event}] event was received {$count} times instead of {$times} times."
            );
        }

        /**
         * Determine if the given event has been received.
         *
         * @param  string  $event
         * @return bool
         */
        public function hasReceived($event)
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
