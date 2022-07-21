<?php

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
