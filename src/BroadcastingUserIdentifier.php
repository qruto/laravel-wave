<?php

namespace Qruto\Wave;

use Illuminate\Contracts\Auth\Authenticatable;

trait BroadcastingUserIdentifier
{
    protected function userKey(Authenticatable $user): string
    {
        return method_exists($user, 'getAuthIdentifierForBroadcasting')
            ? $user->getAuthIdentifierForBroadcasting()
            : $user->getAuthIdentifier();
    }
}
