<p align="center">
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://github.com/qruto/laravel-wave/raw/HEAD/art/logo-dark.svg">
        <source media="(prefers-color-scheme: light)" srcset="https://github.com/qruto/laravel-wave/raw/HEAD/art/logo-light.svg">
        <img alt="Laravel Wave Logo" src="https://github.com/qruto/laravel-wave/raw/HEAD/art/logo-light.svg" width="400">
    </picture>
</p>

<p align="center">Bring <strong>live</strong> to your application</p>

<p align="center">
    <a href="https://github.com/qruto/laravel-wave/actions/workflows/tests.yml"><img src="https://github.com/qruto/laravel-wave/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
    <a href="https://github.com/qruto/laravel-wave/actions/workflows/code-style.yml"><img src="https://github.com/qruto/laravel-wave/actions/workflows/code-style.yml/badge.svg" alt="Code Style"></a>
    <a href="https://github.com/qruto/laravel-wave/actions/workflows/static-analyze.yml"><img src="https://github.com/qruto/laravel-wave/actions/workflows/static-analyze.yml/badge.svg" alt="Static Analyze"></a>
    <a href="https://packagist.org/packages/qruto/laravel-wave"><img src="https://img.shields.io/packagist/dt/qruto/laravel-wave" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/qruto/laravel-wave"><img src="https://img.shields.io/packagist/v/qruto/laravel-wave" alt="Latest Stable Version"></a>
</p>

<p align="center">
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://github.com/qruto/laravel-wave/raw/HEAD/art/connection-demo-dark.png">
        <source media="(prefers-color-scheme: light)" srcset="https://github.com/qruto/laravel-wave/raw/HEAD/art/connection-demo-light.png">
        <img alt="Laravel Wave Logo" src="https://github.com/qruto/laravel-wave/raw/HEAD/art/connection-demo-light.png" width="400">
    </picture>
</p>

# Introduction

Laravel has brilliant [broadcasting system](https://laravel.com/docs/master/broadcasting) for sending events from server to client. Imagine that real-time broadcasting is possible through native HTTP without any WebSockets setup.

🗼 Meet the [**Server-sent Events**](https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events)! Which works with default `redis` broadcasting driver and supports [Laravel Echo](https://github.com/laravel/echo). SSE is specially tuned to send events from the server to client through the HTTP protocol.

## Support

I have spent a lot of effort playing with SSE, Laravel broadcasting system and Redis 👨‍💻 to prepare **Laravel Wave** and make it available for everyone. Since of February 24, unfortunately I haven't any commercial work, permanent living place or the ability to plan anything for the long term. However, I have a greater desire to continue creating useful solutions for people around the world. It makes me feel better these days.

[![support me](https://raw.githubusercontent.com/slavarazum/slavarazum/main/support-banner.png)](https://ko-fi.com/slavarazum)

Now I'm trying to setup GitHub Sponsorships, but it currently not available for Ukrainian bank accounts. Searching for solutions through the 3rd party fiscal host. Looks like it requires some stars on the repository ❤️ ⭐

I would be very grateful for mentions or just a sincere "thank you" 🤝

## Installation

First release 🎉 Works well at home, but should be battle tested before **1.0**. Feedbacks appreciated!

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

After installation, the server is ready to send broadcast events. Let's setup the client part.

### With Laravel Echo

Import Laravel Echo with `WaveConnector` and pass it to the broadcaster option:

```javascript
import Echo from 'laravel-echo';

import { WaveConnector } from 'laravel-wave';

window.Echo = new Echo({ broadcaster: WaveConnector });
```

By default, you can find Echo connection sources in **resources/js/bootstrap.js** file.

Replace it by the snippet above:
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

+ window.Echo = new Echo({ broadcaster: WaveConnector });
```

</details>

### Wave Models

With native conventions of [Model Events Broadcasting](https://laravel.com/docs/8.x/broadcasting#model-broadcasting)
and [Broadcast Notifications](https://laravel.com/docs/8.x/notifications#broadcast-notifications) you can use
Wave models to receive predefined events.

```javascript
import Wave from 'laravel-wave';

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

Let's start by passing model name and key to the `model` method of the Wave instance.

By default Wave prefixes model name with `App.Models` namespace. You can override it with `namespace` option:

```javascript
window.Wave = new Wave({ namespace: 'App.Path.Models' });
```

## Persistent Connection / Fighting with Timeouts

Depend on web server configuration you may notice that the connection drops at a certain interval. Wave automatically reconnecting after request timeout. Don't worry to lost events during reconnection, Laravel Wave stores events history in one minute by default. You can change `resume_lifetime` value in the config file.

> ❇️ Interval between events should be less than web server request timeout and no other low-level timeout options set, to save connection persisted.

By default Wave try to send ping event during SSE connection request if the last event occurred earlier than the number of seconds set in `ping.frequency` config value.
If application does not expect SSE connections frequently. You can specify the environment for which a ping event will be sent on each Wave request.
Set to `local` by default.

### Manual Ping Control

If you want to control ping event by your own, disable automatic sending in `ping.enable` config value.

Laravel Wave provides simple `sse:ping` command which can send single ping or working with interval.

[Tasks scheduler](https://laravel.com/docs/9.x/scheduling#introduction) can help send ping events every minute:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('sse:ping')->everyMinute();
}
```

When you need shorter interval between ping events, run command with `--interval` option which receives number of seconds:

```bash
php artisan sse:ping --interval=30
```

For example, basic `fastcgi_read_timeout` value is `60s` for Nginx + PHP FastCGI server setup. Which means that events in the connection must occur more often than 60 seconds to save it persistent.

### Web Server

Looks like web servers weren't expect persisted HTTP connections and set traps at several stages 😟

Using Nginx + PHP FPM setup, usually connection limited to `1m` by [FastCGI](https://www.php.net/manual/install.fpm.php).

Add next location directive after the end of `location ~ \.php$` body:

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

**\*** copy `fastcgi_pass` unix socket path from `location ~ \.php$`.

### Low Level PHP FPM Timeout

For example, [Laravel Forge](https://forge.laravel.com) configures PHP FPM pool with `request_terminate_timeout = 60` which forces to terminate all requests after 60 seconds.

You can disable it in `/etc/php/8.1/fpm/pool.d/www.conf` config file:

```ini
request_terminate_timeout = 0
```

or configure another pool for SSE connection:

_Writing instruction..._

## Configuration

You can publish the config file with:

```bash
php artisan vendor:publish --tag="wave-config"
```

This is the contents of the published config file:

```php
return [

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

    /*
    |--------------------------------------------------------------------------
    | Ping
    |--------------------------------------------------------------------------
    |
    | Automatically sends a ping event during SSE connection request if the
    | last event occurred before the `frequency` value set in seconds.
    | It's necessary to keep the connection persisted.
    |
    | By setting `eager_env` option a ping event will be sent each request.
    | It suits for development purposes or in case if the application
    | is not expecting events frequently. Accepts `array` or `null`.
    |
    | For manual ping event control with `sse:ping` command
    | you can disable this option.
    */
    'ping' => [
        'enable' => true,
        'frequency' => 30,
        'eager_env' => 'local', // null or array
    ],

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
     | that it registers. When necessary, you may modify these middleware;
     | however, this default value is usually sufficient.
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

- [Slava Razum](https://github.com/slavarazum)
- [All Contributors](../../contributors)

Package template based on [Spatie Laravel Skeleton](https://github.com/spatie/package-skeleton-laravel).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
