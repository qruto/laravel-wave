<?php

namespace Qruto\LaravelWave\Storage;

use Qruto\LaravelWave\Sse\ServerSentEvent;

//TODO: make readonly after update minimum required PHP version
class BroadcastingEvent
{
    public int $timestamp;

    public function __construct(
        public string $channel,
        //TODO: rename to name
        public string $event,
        public string $id,
        public string|array $data,
        public ?string $socket,
    ) {
        $this->timestamp = now()->getTimestamp();
    }

    public function send(): void
    {
        (new ServerSentEvent(
            sprintf('%s.%s', $this->channel, $this->event),
            is_array($this->data) ? json_encode($this->data, JSON_THROW_ON_ERROR) : $this->data,
            $this->id,
            config('wave.retry', null),
        ))();
    }

    public static function fake(array $attributes = []): self
    {
        return new self(
            channel: $attributes['channel'] ?? fake()->word,
            event: $attributes['event'] ?? fake()->word,
            id: $attributes['id'] ?? fake()->uuid,
            data: $attributes['data'] ?? ['message' => fake()->sentence],
            socket: $attributes['socket'] ?? fake()->randomNumber(6, true).'.'.fake()->randomNumber(6, true),
        );
    }
}
