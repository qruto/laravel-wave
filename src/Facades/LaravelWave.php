<?php

namespace Furio\LaravelWave\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Furio\LaravelWave\LaravelWave
 */
class LaravelWave extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-wave';
    }
}
