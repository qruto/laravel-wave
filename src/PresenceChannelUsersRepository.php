<?php

namespace Qruto\LaravelWave;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

interface PresenceChannelUsersRepository
{
    public function join(string $channel, Authenticatable $user, string $connectionId): bool;

    public function leave(string $channel, Authenticatable $user, string $connectionId): bool;

    public function getUsers(string $channel, $user);

    public function getChannels(Authenticatable $user): Collection;
}
