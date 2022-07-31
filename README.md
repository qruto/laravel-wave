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

What do you think about when application needs realtime, live-updating functionality? The answer is usually obvious – WebSockets.
It means you need third-party services like Pusher, Ably or another option is to use self-hosted WebSocket server which requires additional setup and maintenance. Run the server, setup connection through specific port or with reverse proxy, SSL configuration, keeping the socket server running with tool like `supervisord`, scaling configuration, etc. It frequently feels like an overkill for simple notification system or UI updates, given that WebSockets works for receiving and sending data, but is used mainly for sending events from server to client.

Laravel has brilliant [broadcasting system](https://laravel.com/docs/master/broadcasting) for sending events from server to client. Previously, it was closely related to the WebSockets technology. Imagine that realtime, live-updating is possible without all of these extra steps listed above.

🗼 Meet the [**Server-sent Events**](https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events) technology! Which works with default `redis` broadcasting driver and supports [Laravel Echo](https://github.com/laravel/echo). SSE is specially tuned to send events from the server through the HTTP protocol.

## Support

I have spent a lot of time playing with SSE, Laravel broadcasting and Redis to prepare **Laravel Wave** and make it available for everyone. Since of February 24, unfortunately I haven't any commercial work, home, stable available time or the ability to plan anything for the long term. However, I have a greater desire to continue creating useful solutions for people around the world. It makes me feel better these days.

[![support me](https://raw.githubusercontent.com/slavarazum/slavarazum/main/support-banner.png)](https://ko-fi.com/slavarazum)

Now I'm trying to setup GitHub Sponsorships, but it currently not available for Ukrainian bank accounts. Searching for solutions through the 3rd party fiscal host. Looks like it requires some stars on the repository ❤️ ⭐

I would be very grateful for mentions or just a sincere "thank you".

## Installation

You can install packages to server and client sides via composer with npm:

```bash
composer require qruto/laravel-wave
npm install laravel-wave
```

Change broadcast driver in your `.env` file:

```ini
BROADCAST_DRIVER=redis
```

## Usage

> ⚠️ On the event-stream connection request, server runs [Redis subscription process](https://laravel.com/docs/9.x/redis#wildcard-subscriptions), however it can't detect request disconnection to kill the subscriber until next event has been received.

If your application will not send events frequently, use ping command to close outdated workers.

[Tasks scheduler](https://laravel.com/docs/9.x/scheduling#introduction) can help send ping events every minute:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('sse:ping')->everyMinute();
}
```

When you need smaller interval between ping events, run the command with `--interval` option which receives number in seconds:

```bash
php artisan sse:ping --interval=30
```

For example, basic `fastcgi_read_timeout` set to 60s. Which means that events in the connection must occur more often than 60 seconds.

### With Laravel Echo

Import Laravel Echo with `WaveConnector` and pass it to the broadcaster option:

```javascript
import Echo from 'laravel-echo';

import { WaveConnector } from 'laravel-wave';

window.Echo = new Echo({
   broadcaster: WaveConnector,
});
```

By default, you can find Echo connection in **resources/js/bootstrap.js**.

You can replace it by the snippet above:
<details>
    <summary>Show diff</summary>

```diff
- import Echo from 'laravel-echo';

- import Pusher from 'pusher-js';
- window.Pusher = Pusher;

- window.Echo = new Echo({
-     broadcaster: 'pusher',
-     key: import.meta.env.VITE_PUSHER_APP_KEY,
-     wsHost: import.meta.env.VITE_PUSHER_HOST ?? `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
-     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
-     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
-     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
-     enabledTransports: ['ws', 'wss'],
- });
+ import Echo from 'laravel-echo';

+ import { WaveConnector } from 'laravel-wave';

+ window.Echo = new Echo({
+     broadcaster: WaveConnector,
+ });
```

</details>

Now you can use [Echo and Broadcasting](https://laravel.com/docs/8.x/broadcasting#broadcasting-to-presence-channels) system as usual with all of the supported features!

### Wave Models

In Laravel we have great native abilities for [Model Events Broadcasting](https://laravel.com/docs/8.x/broadcasting#model-broadcasting)
and [Broadcast Notifications](https://laravel.com/docs/8.x/notifications#broadcast-notifications).

**Laravel Wave** provides a clear api to receive it.

```javascript
import { Wave } from 'laravel-wave';

window.Wave = new Wave();

wave.model('User', '1')
    .notification('team.invite', (notification) => {
        console.log(notification);
    })
    .updated((user) => console.log('user updated', user))
    .deleted((user) => console.log('user deleted', user))
    .trashed((user) => console.log('user trashed', user))
    .restored((user) => console.log('user restored', user))
    .updated('Team', (team) => console.log('team updated', team));
```

Firstly, we should pass model name and id to the `model` method of the Wave instance.
By default Wave prefixed model name with `App.Models` namespace. You can override it with `namespace` option:

```javascript
window.Wave = new Wave({ namespace: 'App.Path.Models' });
```

### Optional

You can publish the config file with:

```bash
php artisan vendor:publish --tag="wave-config"
```

This is the contents of the published config file:

```php
return [

    /*
    |--------------------------------------------------------------------------
    | Routes Path
    |--------------------------------------------------------------------------
    |
    | This path will be used to register necessary routes for Wave connection,
    | presence channel users storing and simple whisper events.
    |
    */
    'path' => 'wave',

    /*
     |--------------------------------------------------------------------------
     | Route Middleware
     |--------------------------------------------------------------------------
     |
     | Here you may specify which middleware Wave will assign to the routes
     | that it registers with the package. When necessary, you may modify
     | these middleware; however, this default value is usually sufficient.
     |
     */
    'middleware' => [
        'web',
    ],

    /*
     |--------------------------------------------------------------------------
     | Auth & Guard
     |--------------------------------------------------------------------------
     |
     | Default authentication middleware and guard type for authenticate
     | users for presence channels and whisper events
     |
     */
    'auth_middleware' => 'auth',

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Resume Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of seconds that you wish an event stream
    | to be persisted to resume it after reconnect. The connection is
    | immediately re-established every closed response.
    |
    */
    'resume_lifetime' => 60,
];
```

If you want to change base path from `wave` to another, don't forget to pass it in `Echo` or `Wave` instance:

```javascript
window.Echo = new Echo({
   broadcaster: WaveConnector,
   endpoint: 'custom-path',
});

// or

window.Wave = new Wave({ endpoint: 'custom-path' });
```

## Persistent Connection / Fighting with Timeouts

Depend on web server configuration you may notice that the connection drops at a certain interval. SSE automatically reconnecting after request timeout. Don't worry to lost events during reconnection, Laravel Wave stores events history in one minute. You can change `resume_lifetime` value in config file.

Looks like http and web servers weren't ready for persisted connections and set traps at several stages. Some of them disables on the package level:

- [default_socket_timeout](https://www.php.net/manual/ru/filesystem.configuration.php#ini.default-socket-timeout) set to `-1`
- [max_execution_time](https://www.php.net/manual/en/info.configuration.php#ini.max-execution-time) set to `0` by [set_time_limit](https://www.php.net/manual/ru/function.set-time-limit) function

### Web Server

Using Nginx as a web server, usually connection limited to `1m` by [FastCGI](https://www.php.net/manual/install.fpm.php).

Add next location directive below the end of `location ~ \.php$`:

```nginx
location = /wave {
    rewrite ^/wave$ /index.php?$query_string break;
    fastcgi_split_path_info ^(.+\.php)(/.+)$;
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_read_timeout 2m;
}
```

__*__ copy `fastcgi_pass` unix socket path from `location ~ \.php$`.

> ❇️ If the interval between events will be less than value set in `fastcgi_read_timeout` option and there are no other timeout options set, connection will be persisted.

### PHP FPM Timeouts

For example, [Laravel Forge](https://forge.laravel.com) configures PHP FPM pool with `request_terminate_timeout = 60` which forces to terminate all requests after 60 seconds.

You can disable it in `/etc/php/8.1/fpm/pool.d/www.conf`:

```ini
request_terminate_timeout = 0
```

or configure another pool for SSE connection:

_Writing instruction..._

## Future Plans

- 📍 local broadcasting driver
- ◻️ Laravel Octane support
- 📥 📤 two ways live models syncing
- 📡 Something awesome with opened live abilities...

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

+ [Slava Razum](https://github.com/slavarazum)
+ [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
