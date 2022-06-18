<?php

namespace Qruto\LaravelWave\Tests\Support;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as IlluminateUser;

class User extends IlluminateUser
{
    use HasFactory;
}
