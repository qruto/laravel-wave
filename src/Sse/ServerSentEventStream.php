<?php

namespace Qruto\LaravelWave\Sse;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Str;
use Qruto\LaravelWave\ServerSentEventSubscriber;
use Qruto\LaravelWave\Storage\BroadcastEventHistory;
use Qruto\LaravelWave\Storage\PresenceChannelUsersRedisRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ServerSentEventStream implements Responsable
{
    /**
     * @var array<string, string>
     */
    protected const HEADERS = [
        'Content-Type' => 'text/event-stream',
        'Connection' => 'keep-alive',
        'Cache-Control' => 'no-cache, no-store, must-revalidate, pre-check=0, post-check=0',
        'X-Accel-Buffering' => 'no',
    ];

    protected string $channelPrefix = '';

    public function __construct(
        protected ServerSentEventSubscriber $eventSubscriber,
        protected ResponseFactory $responseFactory,
        protected PresenceChannelUsersRedisRepository $store,
        protected BroadcastEventHistory $eventsHistory,
        protected ConfigRepository $config
    ) {
        $this->channelPrefix = config('database.redis.options.prefix', '');
    }

    public function toResponse($request)
    {
        ini_set('default_socket_timeout', -1);
        set_time_limit(0);

        $socket = Broadcast::socket($request);

        return $this->responseFactory->stream(function () use ($request, $socket) {
            (new ServerSentEvent(
                'connected',
                $socket,
                $request->hasHeader('Last-Event-ID') ? $request->header('Last-Event-ID') : 'wave',
                $this->config->get('wave.retry', null),
            ))();

            $handler = $this->eventHandler($request, $socket);

            if ($request->hasHeader('Last-Event-ID')) {
                $missedEvents = $this->eventsHistory->getEventsFrom($request->header('Last-Event-ID'), $this->channelPrefix);

                $missedEvents->each(static function ($event) use ($handler) {
                    $handler($event['event'], $event['channel']);
                });
            }

            $this->eventSubscriber->start($handler, $request);
        }, Response::HTTP_OK, self::HEADERS + ['X-Socket-Id' => $socket]);
    }

    protected function eventHandler(Request $request, string $socket): Closure
    {
        return function ($message, $channel) use ($request, $socket) {
            $channel = $this->removePrefixFromChannel($channel);

            if ($this->needsAuth($channel)) {
                try {
                    $this->authChannel($channel, $request);
                } catch (AccessDeniedHttpException) {
                    return;
                }
            }

            ['event' => $event, 'data' => $data] = is_array($message) ? $message : json_decode($message, true, 512, JSON_THROW_ON_ERROR);

            $eventSocketId = Arr::pull($data, 'socket');
            $eventId = Arr::pull($data, 'broadcast_event_id');

            if ($eventSocketId === $socket) {
                return;
            }

            (new ServerSentEvent(
                sprintf('%s.%s', $channel, $event),
                json_encode($data, JSON_THROW_ON_ERROR),
                sprintf('%s.%s', $channel, $eventId)
            ))();
        };
    }

    protected function authChannel(string $channel, Request $request): void
    {
        Broadcast::auth($request->merge([
            'channel_name' => $channel,
        ]));
    }

    protected function needsAuth(string $channel): bool
    {
        return str_starts_with($channel, 'private-') || str_starts_with($channel, 'presence-');
    }

    protected function removePrefixFromChannel(string $pattern): string
    {
        return Str::after($pattern, $this->channelPrefix);
    }
}
