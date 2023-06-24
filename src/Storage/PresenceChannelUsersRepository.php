<?php

namespace Qruto\LaravelWave\Storage;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

interface PresenceChannelUsersRepository
{
    public function join(string $channel, Authenticatable $user, array $userInfo, string $connectionId): bool;

    public function leave(string $channel, Authenticatable $user, string $connectionId): bool;

    public function getUsers(string $channel);

    public function removeConnection(string $connectionId): array;
}
