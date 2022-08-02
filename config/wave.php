<?php

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
