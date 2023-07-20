<?php

namespace Qruto\LaravelWave\Sse;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Qruto\LaravelWave\BroadcastingUserIdentifier;
use Qruto\LaravelWave\ServerSentEventSubscriber;
use Qruto\LaravelWave\Storage\BroadcastEventHistory;
use Qruto\LaravelWave\Storage\BroadcastingEvent;
use Qruto\LaravelWave\Storage\PresenceChannelUsersRedisRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ServerSentEventStream implements Responsable
{
    use BroadcastingUserIdentifier;

    /**
     * @var array<string, string>
     */
    protected const HEADERS = [
        'Content-Type' => 'text/event-stream',
        'Connection' => 'keep-alive',
        'Cache-Control' => 'no-cache, no-store, must-revalidate, pre-check=0, post-check=0',
        'X-Accel-Buffering' => 'no',
    ];

    public function __construct(
        protected ServerSentEventSubscriber $eventSubscriber,
        protected ResponseFactory $responseFactory,
        protected PresenceChannelUsersRedisRepository $store,
        protected BroadcastEventHistory $eventsHistory,
        protected ConfigRepository $config
    ) {
    }

    public function toResponse($request)
    {
        $this->disableTimeouts();

        $lastSocket = Broadcast::socket($request);

        $newSocket = $this->generateConnectionId();

        $request->headers->set('X-Socket-ID', $newSocket);

        return $this->responseFactory->stream(function () use ($request, $lastSocket, $newSocket) {
            if ($request->hasHeader('Last-Event-ID')) {
                $missedEvents = $this->eventsHistory->getEventsFrom($request->header('Last-Event-ID'));

                $missedEvents
                    ->filter(fn (BroadcastingEvent $event) => $event->event !== 'connected')
                    ->each($this->eventHandler($request, $lastSocket));
            }

            $event = EventFactory::create('general', 'connected', $newSocket, $newSocket);

            // TODO: change general channel name
            $this->eventsHistory->pushEvent($event);

            $event->send();

            $this->eventSubscriber->start(function (string $message, string $channel) use ($request, $newSocket) {
                $this->eventHandler($request, $newSocket)(EventFactory::fromRedisMessage($message, $channel));
            }, $request, $newSocket);
        }, Response::HTTP_OK, self::HEADERS + ['X-Socket-Id' => $newSocket]);
    }

    protected function eventHandler(Request $request, ?string $socket): Closure
    {
        return function (BroadcastingEvent $event) use ($request, $socket) {
            if ($this->needsAuth($event->channel)) {
                try {
                    $this->authChannel($event->channel, $request);
                } catch (AccessDeniedHttpException) {
                    return;
                }
            }

            if ($this->shouldNotSend($event, $socket, $request->user())) {
                return;
            }

            $event->send();
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

    protected function shouldNotSend(BroadcastingEvent $event, ?string $socket, ?Authenticatable $user): bool
    {
        if (! $socket) {
            return false;
        }

        return $event->socket === $socket;
    }

    private function disableTimeouts(): void
    {
        ini_set('default_socket_timeout', -1);
        set_time_limit(0);
    }

    private function generateConnectionId(): string
    {
        return sprintf('%d.%d', random_int(1, 1_000_000_000), random_int(1, 1_000_000_000));
    }
}
