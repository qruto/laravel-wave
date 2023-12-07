<?php

namespace Qruto\LaravelWave\Tests;

trait InteractsWithRedis
{
    use \Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;

    public static function redisDriverProvider()
    {
        return [
            ['phpredis'],
        ];
    }

    public function connection()
    {
        return $this->redis['phpredis']->connection();
    }
}
