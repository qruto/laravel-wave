<?php

namespace Qruto\LaravelWave\Tests\Support\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Qruto\LaravelWave\Tests\Support\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ];
    }
}
