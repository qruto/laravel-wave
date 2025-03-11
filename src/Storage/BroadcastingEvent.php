<?php

namespace Qruto\Wave\Storage;

use Qruto\Wave\Sse\ServerSentEvent;

// TODO: make readonly after update minimum required PHP version
class BroadcastingEvent
{
    public function __construct(
        public string $channel,
        public string $name,
        public string|array $data,
        public ?string $id,
        public ?string $socket,
    ) {}

    public function send(): void
    {
        (new ServerSentEvent(
            sprintf('%s.%s', $this->channel, $this->name),
            is_array($this->data) ? json_encode($this->data, JSON_THROW_ON_ERROR) : $this->data,
            $this->id,
            config('wave.retry', null),
        ))();
    }

    public static function fake(array $attributes = []): self
    {
        return new self(
            channel: $attributes['channel'] ?? fake()->word,
            name: $attributes['event'] ?? fake()->word,
            id: null,
            data: $attributes['data'] ?? ['message' => fake()->sentence],
            socket: $attributes['socket'] ?? fake()->randomNumber(6, true).'.'.fake()->randomNumber(6, true),
        );
    }
}
