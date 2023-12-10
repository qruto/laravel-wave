<?php

namespace Qruto\Wave\Storage;

use Illuminate\Contracts\Auth\Authenticatable;

interface PresenceChannelUsersRepository
{
    public function join(string $channel, Authenticatable $user, array $userInfo, string $connectionId): bool;

    public function leave(string $channel, Authenticatable $user, string $connectionId): bool;

    public function getUsers(string $channel);

    public function removeConnection(Authenticatable $user, string $connectionId): array;
}
