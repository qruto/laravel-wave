<?php

namespace Qruto\LaravelWave\Sse;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Broadcast;
use Qruto\LaravelWave\PresenceChannelUsersRepository;
use Qruto\LaravelWave\ServerSentEventSubscriber;
use Symfony\Component\HttpFoundation\Response;

class ServerSentEventStream implements Responsable
{
    private const HEADERS = [
        'Content-Type' => 'text/event-stream',
        'Connection' => 'keep-alive',
        'Cache-Control' => 'no-cache',
        'X-Accel-Buffering' => 'no',
    ];

    public function __construct(
        protected ServerSentEventSubscriber $eventSubscriber,
        protected ResponseFactory $responseFactory,
        protected PresenceChannelUsersRepository $store,
    ) {
    }

    public function toResponse($request)
    {
        return $this->responseFactory->stream(function () use ($request) {
            (new ServerSentEvent('connected', Broadcast::socket()))();

            $this->eventSubscriber->start($this->eventHandler($request));
        }, Response::HTTP_OK, self::HEADERS);
    }

    protected function eventHandler(Request $request)
    {
        return function ($message, $channel) use ($request) {
            if ($this->needsAuth($channel)) {
                $this->authChannel($channel, $request);
            }

            ['event' => $event, 'data' => $data] = json_decode($message, true);
            $socket = Arr::pull($data, 'socket');

            if ($socket === Broadcast::socket()) {
                return;
            }

            (new ServerSentEvent(
                "$channel.$event",
                json_encode(['data' => $data])
            ))();
        };
    }

    protected function needsAuth(string $channel): bool
    {
        return str_starts_with($channel, 'private-') || str_starts_with($channel, 'presence-');
    }

    protected function authChannel(string $channel, Request $request): void
    {
        Broadcast::auth($request->merge([
            'channel_name' => $channel,
        ]));
    }
}
