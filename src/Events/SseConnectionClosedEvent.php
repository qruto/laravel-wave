<?php

namespace Qruto\LaravelWave\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Queue\SerializesModels;

class SseConnectionClosedEvent
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ?Authenticatable $user, public string $connectionId)
    {
    }
}
