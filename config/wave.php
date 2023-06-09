<?php

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
    | * Requires a cache driver to be configured.
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
