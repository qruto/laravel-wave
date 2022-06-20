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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
        /** @var string */
        $socket = Broadcast::socket($request);

        return $this->responseFactory->stream(function () use ($request, $socket) {
            (new ServerSentEvent('connected', $socket))();

            $this->eventSubscriber->start($this->eventHandler($request, $socket));
        }, Response::HTTP_OK, self::HEADERS + ['X-Socket-Id' => $socket]);
    }

    protected function eventHandler(Request $request, string $socket)
    {
        return function ($message, $channel) use ($request, $socket) {
            if ($this->needsAuth($channel)) {
                ray('need auth', $request->user()->name, $channel, $message);

                try {
                    $this->authChannel($channel, $request);
                } catch (AccessDeniedHttpException $e) {
                    ray('denied');

                    return;
                }
            }

            ['event' => $event, 'data' => $data] = json_decode($message, true);
            $eventSocket = Arr::pull($data, 'socket');

            if ($eventSocket === $socket) {
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
