<?php

namespace Qruto\Wave;

use Illuminate\Foundation\Application;

if (! function_exists('laravel11OrHigher')) {
    function laravel11OrHigher(): bool
    {
        return explode('.', Application::VERSION)[0] >= 11;
    }
}
