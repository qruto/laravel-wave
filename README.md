<p align="center">
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://github.com/qruto/laravel-wave/raw/HEAD/art/logo-dark.svg">
        <source media="(prefers-color-scheme: light)" srcset="https://github.com/qruto/laravel-wave/raw/HEAD/art/logo-light.svg">
        <img alt="Laravel Wave Logo" src="https://github.com/qruto/laravel-wave/raw/HEAD/art/logo-light.svg" width="400">
    </picture>
</p>

<p align="center">
    <a href="https://github.com/qruto/laravel-wave/actions/workflows/tests.yml"><img src="https://github.com/qruto/laravel-wave/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
    <a href="https://github.com/qruto/laravel-wave/actions/workflows/code-style.yml"><img src="https://github.com/qruto/laravel-wave/actions/workflows/code-style.yml/badge.svg" alt="Code Style"></a>
    <a href="https://github.com/qruto/laravel-wave/actions/workflows/static-analyze.yml"><img src="https://github.com/qruto/laravel-wave/actions/workflows/static-analyze.yml/badge.svg" alt="Static Analyze"></a>
    <a href="https://packagist.org/packages/qruto/laravel-wave"><img src="https://img.shields.io/packagist/dt/qruto/laravel-wave" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/qruto/laravel-wave"><img src="https://img.shields.io/packagist/v/qruto/laravel-wave" alt="Latest Stable Version"></a>
</p>

# Introduction

What do you think about when your need implement realtime, live-updating user interfaces? The answer is usually obvious – WebSockets.
This means you need third-party services like Pusher or Ably. Another option is to use self-hosted WebSocket server which requires additional setup and maintenance. Run the server, setup connection through specific port or with reverse proxy, SSL configuration, keeping the socket server running with tool like `supervisord`, scaling configuration, etc. It frequently feels like an overkill for simple notification system or UI updates, given that WebSockets works for receiving and sending data, but is used mainly for receiving.

Laravel has brilliant [broadcasting system](https://laravel.com/docs/master/broadcasting) for sending events from server to client. Previously, it was closely related to the WebSockets technology. Imagine that realtime, live-updating is possible without all of these extra steps listed above.

🗼 Meet the [**Server-sent Events**](https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events) technology! Which works with default `redis` broadcasting driver and supports [Laravel Echo](https://github.com/laravel/echo). The technology is specially tuned to send events from the server through the HTTP protocol.

## Support

I have spent a lot of time to investigate SSE technology, Laravel broadcasting system, Redis driver for implement and pack this solution to make available it free for everyone. Since of February 24, unfortunately I haven't any commercial work, home, stable available time or the ability to plan anything for the long term. However, I have a greater desire to continue creating useful solutions for people around the world. It makes me feel better these days.

Now I'm trying to setup native GitHub Sponsorships, but it currently not available for Ukrainian bank accounts. Searching for solutions through the 3rd party fiscal host. Looks like it requires some stars on the repository ❤️ ⭐

[![support me](https://raw.githubusercontent.com/slavarazum/slavarazum/main/support-banner.png)](https://ko-fi.com/slavarazum)

I would be very grateful for mentions or just a sincere "thank you".

## Installation

Currently requires `redis`.

You can install the package via composer:

```bash
composer require qruto/laravel-wave
npm install laravel-wave
```

Change broadcast driver in your `.env` file:

```ini
BROADCAST_DRIVER=redis
```

## Usage

### Optional

You can publish the config file with:

```bash
php artisan vendor:publish --tag="wave-config"
```

This is the contents of the published config file:

```php
return [

    /*
     * This path will be used to register the necessary routes for the package.
     */
    'path' => 'wave',

    /*
     * Middleware for storing presence channel user routes.
     */
    'middleware' => [
        'web',
    ],

    'auth_middleware' => 'auth',

    'guard' => 'web',
];
```

## Testing

```bash
composer test
```

## Knowing Issues

- redis subscription lifetime limited to 60 seconds
- no ability stop script immediately on disconnect
- suggest to better typing

Potential solutions:

```php
ini_set('default_socket_timeout', -1);
set_time_limit(0);
Redis::connection('subscription')->setOption(\Redis::OPT_READ_TIMEOUT, -1);
```

## TODO

- Local driver
- Stream continue
- Laravel Octane

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Slava Razum](https://github.com/slavarazum)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
