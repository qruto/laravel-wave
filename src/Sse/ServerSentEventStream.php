<?php

namespace Qruto\LaravelWave\Sse;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Str;
use Qruto\LaravelWave\EventsStorage;
use Qruto\LaravelWave\PresenceChannelUsersRedisRepository;
use Qruto\LaravelWave\ServerSentEventSubscriber;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ServerSentEventStream implements Responsable
{
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
        protected EventsStorage $eventsHistory,
    ) {
        $this->channelPrefix = config('database.redis.options.prefix', '');
    }

    public function toResponse($request)
    {
        /** @var string */
        $socket = Broadcast::socket($request);

        return $this->responseFactory->stream(function () use ($request, $socket) {
            (new ServerSentEvent('connected', $socket))();

            $handler = $this->eventHandler($request, $socket);

            if ($request->hasHeader('Last-Event-ID')) {
                $missedEvents = $this->eventsHistory->getEventsFrom($request->header('Last-Event-ID'), $this->channelPrefix);

                $missedEvents->each(function ($event) use ($handler) {
                    $handler($event['event'], $event['channel']);
                });
            }

            $this->eventSubscriber->start($handler, $request);
        }, Response::HTTP_OK, self::HEADERS + ['X-Socket-Id' => $socket]);
    }

    protected function eventHandler(Request $request, string $socket)
    {
        return function ($message, $channel) use ($request, $socket) {
            $channel = $this->removePrefixFromChannel($channel);

            if ($this->needsAuth($channel)) {
                try {
                    $this->authChannel($channel, $request);
                } catch (AccessDeniedHttpException $e) {
                    return;
                }
            }

            ['event' => $event, 'data' => $data] = is_array($message) ? $message : json_decode($message, true);

            // TODO: Test if it data exists with websockets
            $eventSocketId = Arr::pull($data, 'socket');
            // TODO: Change uuid name
            $uuid = Arr::pull($data, 'uuid');

            if ($eventSocketId === $socket) {
                return;
            }

            (new ServerSentEvent(
                "$channel.$event",
                json_encode(['data' => $data]),
                "$channel.$uuid"
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
