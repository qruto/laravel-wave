<p align="center">
    <picture>
        <source 
            width="350" 
            media="(prefers-color-scheme: dark)"
            srcset="https://github.com/qruto/laravel-wave/raw/HEAD/art/logo-dark.png"
        >
        <source
            width="350"
            media="(prefers-color-scheme: light)"
            srcset="https://github.com/qruto/laravel-wave/raw/HEAD/art/logo-light.png"
        >
        <img
            alt="Laravel Wave Logo"
            src="https://github.com/qruto/laravel-wave/raw/HEAD/art/logo-light.png"
            width="350"
        >
    </picture>
</p>

<p align="center">Bring <strong>live</strong> to your application</p>

<p align="center">
    <a href="https://github.com/qruto/laravel-wave/actions/workflows/tests.yml"><img src="https://github.com/qruto/laravel-wave/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
    <a href="https://github.com/qruto/laravel-wave/actions/workflows/styles.yml"><img src="https://github.com/qruto/laravel-wave/actions/workflows/styles.yml/badge.svg" alt="Styles check"></a>
    <a href="https://github.com/qruto/laravel-wave/actions/workflows/types.yml"><img src="https://github.com/qruto/laravel-wave/actions/workflows/types.yml/badge.svg" alt="Types check"></a>
    <a href="https://github.com/qruto/laravel-wave/actions/workflows/refactor.yml"><img src="https://github.com/qruto/laravel-wave/actions/workflows/refactor.yml/badge.svg" alt="Refactor code"></a>
    <a href="https://packagist.org/packages/qruto/laravel-wave"><img src="https://img.shields.io/packagist/dt/qruto/laravel-wave" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/qruto/laravel-wave"><img src="https://img.shields.io/packagist/v/qruto/laravel-wave" alt="Latest Stable Version"></a>
</p>

<p align="center">
    <picture>
        <source 
            media="(prefers-color-scheme: dark)"
            srcset="https://github.com/qruto/laravel-wave/raw/HEAD/art/connection-demo-dark.png"
        >
        <source 
            media="(prefers-color-scheme: light)" 
            srcset="https://github.com/qruto/laravel-wave/raw/HEAD/art/connection-demo-light.png"
        >
        <img
            alt="Laravel Wave Demo"
            src="https://github.com/qruto/laravel-wave/raw/HEAD/art/connection-demo-light.png"
            width="400"
        >
    </picture>
</p>

# Introduction

Unlock the power of Laravel's [broadcasting system](https://laravel.com/docs/master/broadcasting)
with **Wave**. Imagine that real-time server broadcasting is possible over native HTTP without any WebSockets setup.

Meet the [**Server-sent Events**](https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events) 🗼 Works seamlessly with Laravel's default `redis` broadcasting driver and supports [Laravel Echo](https://github.com/laravel/echo).

> Server-Sent Events (**SSE**) is specially tuned for real-time server-to-client communication.

Experience it live with our [demo streaming tweets](https://wave.qruto.dev) 🐤.

Works well at home. Should be battle tested for **1.0**, feedbacks appreciated!

## 🌟 Key Features

- **⚡ Native Redis Driver Support**: Wave seamlessly integrates with Laravel's default `redis` broadcasting driver, ensuring efficient real-time data transfer.

- **🔄 Resume From Last**: Connection drops? No problem! Wave intelligently resumes the event stream from the last event, ensuring no crucial data is lost in transit.

- **🟢 Live Models**: With a simple interface that respects Laravel's native conventions for [Model Events Broadcasting](https://laravel.com/docs/master/broadcasting#model-broadcasting)
  and [Broadcast Notifications](https://laravel.com/docs/master/notifications#broadcast-notifications), Wave turbocharges your application with real-time updates.

- **🎛️️ Full Requests Control**: Wave hands you the reins over connection and authentication requests, granting you the freedom to shape your broadcasting setup to your exact requirements.

## Support

I have spent a lot of effort playing with SSE, Laravel broadcasting system and Redis to prepare **Laravel Wave** and make it available for everyone. Since of February 24, unfortunately I haven't any commercial work, permanent living place or the ability to plan anything for the long term. However, I have a greater desire to continue creating useful solutions for people around the world. It makes me feel better these days.

[![support me](https://raw.githubusercontent.com/slavarazum/slavarazum/main/support-banner.png)](https://github.com/sponsors/qruto)

[GitHub Sponsorships profile](https://github.com/sponsors/qruto) is ready! There you can find current work, future plans, goals and dreams... Your stars make me happier each day ✨ Sponsorship will enable us to live more peacefully and continue to work on useful solutions for you.

I would be very grateful for mentions or just a sincere "thank you".

💳 [Sponsoring directly to savings jar](https://send.monobank.ua/jar/3eG4Vafvzq) with card or Apple Pay/Google Pay.


## Installation

Install **Wave** on both server and client sides using Composer and npm:

```bash
composer require qruto/laravel-wave
npm install laravel-wave
```

Then, set your `.env` file to use the `redis` broadcasting driver:

```ini
BROADCAST_DRIVER=redis
```

## Usage

After installing **Wave**, your server is ready to broadcast events.
You can use it with **Echo** as usual or try `Wave` model to work with predefined Eloquent events.

### 1. With Laravel Echo

Import Laravel Echo with `WaveConnector` and pass it to the broadcaster option:

```javascript
import Echo from 'laravel-echo';

import { WaveConnector } from 'laravel-wave';

window.Echo = new Echo({ broadcaster: WaveConnector });
```

For fresh installations, locate Echo connection configuration in **resources/js/bootstrap.js** file.

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

Use Echo as you typically would.

📞 Full documentation of [Receiving Broadcasts](https://laravel.com/docs/master/broadcasting#receiving-broadcasts)

### 2. With Live Eloquent Models

With native conventions of [Model Events Broadcasting](https://laravel.com/docs/master/broadcasting#model-broadcasting)
and [Broadcast Notifications](https://laravel.com/docs/master/notifications#broadcast-notifications) you can use
**Wave** models to receive model events and notifications.

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

Start by calling the `model` method on the `Wave` instance with the model name and key.

By default, Wave prefixes model names with `App.Models` namespace. You can customize this with the `namespace` option:

```javascript
window.Wave = new Wave({ namespace: 'App.Path.Models' });
```

📄 [Check out full Laravel Broadcasting documentation](https://laravel.com/docs/9.x/broadcasting)

## Configuration

### Client Options

These options can be passed to the `Wave` or `Echo` instance:

| Name          | Type                                                                           | Default                 | Description                                                                    |
|---------------|--------------------------------------------------------------------------------|-------------------------|--------------------------------------------------------------------------------|
| endpoint      | _string_                                                                       | `/wave`                 | Primary SSE connection route.                                                  |
| namespace     | _string_                                                                       | `App.Events`            | Namespace of events to listen for.                                             |
| auth.headers  | _object_                                                                       | `{}`                    | Additional authentication headers.                                             |
| authEndpoint  | _string?_                                                                      | `/broadcasting/auth`    | Authentication endpoint.                                                       |
| csrfToken     | _string?_                                                                      | `undefined` or `string` | CSRF token, defaults from `XSRF-TOKEN` cookie.                                 |
| bearerToken   | _string?_                                                                      | `undefined`             | Bearer tokenfor authentication.                                                |
| request       | _[Request](https://developer.mozilla.org/en-US/docs/Web/API/Request/Request)?_ | `undefined`             | Custom settings for connection and authentication requests.                    |
| pauseInactive | _boolean_                                                                      | `false`                 | If `true`, closes connection when the page is hidden and reopens when visible. |

```javascript
new Echo({
    broadcaster: WaveConnector,
    endpoint: '/sse-endpoint',
    bearerToken: 'bearer-token',
    //...
});

// or

new Wave({
    authEndpoint: '/custom-broadcasting/auth',
    csrfToken: 'csrf-token',
})
```


### Server Options

You can publish the Laravel configuration file with:

```bash
php artisan vendor:publish --tag="wave-config"
```

Here are the contents of the published configuration file:

```php
return [

    /*
    |--------------------------------------------------------------------------
    | Resume Lifetime
    |--------------------------------------------------------------------------
    |
    | Define how long (in seconds) you wish an event stream to persist so it
    | can be resumed after a reconnect. The connection automatically
    | re-establishes with every closed response.
    |
    */
    'resume_lifetime' => 60,

    /*
    |--------------------------------------------------------------------------
    | Reconnection Time
    |--------------------------------------------------------------------------
    |
    | This value determines how long (in milliseconds) to wait before
    | attempting a reconnect to the server after a connection has been lost.
    | By default, the client attempts to reconnect immediately. For more
    | information, please refer to the Mozilla developer's guide on event
    | stream format.
    | https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events/Using_server-sent_events#Event_stream_format
    |
    */
    'retry' => null,

    /*
    |--------------------------------------------------------------------------
    | Ping
    |--------------------------------------------------------------------------
    |
    | A ping event is automatically sent on every SSE connection request if the
    | last event occurred before the set `frequency` value (in seconds). This
    | ensures the connection remains persistent.
    |
    | By setting the `eager_env` option, a ping event will be sent with each
    | request. This is useful for development or for applications that do not
    | frequently expect events. The `eager_env` option can be set as an `array` or `null`.
    |
    | For manual control of the ping event with the `sse:ping` command, you can
    | disable this option.
    |
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
    | This path is used to register the necessary routes for establishing the
    | Wave connection, storing presence channel users, and handling simple whisper events.
    |
    */
    'path' => 'wave',

    /*
     |--------------------------------------------------------------------------
     | Route Middleware
     |--------------------------------------------------------------------------
     |
     | Define which middleware Wave should assign to the routes that it registers.
     | You may modify these middleware as needed. However, the default value is
     | typically sufficient.
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
     | Define the default authentication middleware and guard type for
     | authenticating users for presence channels and whisper events.
     |
     */
    'auth_middleware' => 'auth',

    'guard' => 'web',

];
```

## Advanced Setup

### Persistent Connection

Depending on your web server configuration, you might observe that the connection drops at certain intervals.
Wave is designed to automatically reconnect after a request timeout.
During reconnection, you won't lose any events as Laravel Wave
stores event history for one minute by default.
The `resume_lifetime` value in the config file
allows you to adjust this duration.

> ⚠️ Ensure that the interval between events is shorter than your web server's request timeout 
> and that no other low-level timeout options are set to keep the connection persistent

Wave tries to send a ping event on each Server-Sent Events (SSE) connection request
if the last event occurred earlier than the `ping.frequency` config value.

If your application doesn't expect many SSE connections,
specify the list of environments in which a ping event will be sent
with each Wave request. By default, this is set to `local`.

### Manual Ping Control

For more control over the ping event, disable automatic sending by adjusting the `ping.enable` config value.

**Wave** offers the `sse:ping` command for manually sending a single ping or operating at an interval.

Use the Laravel [Tasks scheduler](https://laravel.com/docs/master/scheduling#introduction) to send a ping event every minute:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('sse:ping')->everyMinute();
}
```

If you require shorter intervals between ping events, use the --interval option to set the number of seconds:

```bash
php artisan sse:ping --interval=30
```

For example, basic `fastcgi_read_timeout` value is `60s` for Nginx + PHP FastCGI server setup.
This means that to keep the connection persistent, events must occur more often than every **60 seconds**.

### Web Server Configuration

Web servers usually don't expect persistent HTTP connections and may have limitations at various stages 😟.

For a Nginx + PHP FPM setup, the connection is typically limited to `1m` by [FastCGI](https://www.php.net/manual/install.fpm.php).
Modify the location directive after the end of `location ~ \.php$` as follows:

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

**Note:** Copy the `fastcgi_pass` Unix socket path from `location ~ \.php$`.

### Disabling PHP FPM Timeout

Some platforms, such as [Laravel Forge](https://forge.laravel.com), configure the PHP FPM pool with `request_terminate_timeout = 60`, terminating all requests after 60 seconds.

You can disable this in the `/etc/php/8.1/fpm/pool.d/www.conf` config file:

```ini
request_terminate_timeout = 0
```

Alternatively, you can configure a separate pool for the SSE connection:

_Writing instruction..._

## Future Plans

📍 Local broadcasting driver
◻️ Laravel Octane support
📥 📤 two ways live models syncing
📡 Something awesome with opened live abilities...

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
