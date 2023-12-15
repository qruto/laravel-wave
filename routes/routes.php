<?php

use Illuminate\Support\Facades\Route;
use Qruto\Wave\Http\Controllers\PresenceChannelUsersController;
use Qruto\Wave\Http\Controllers\SendWhisper;
use Qruto\Wave\Http\Controllers\WaveConnection;

Route::group([
    'prefix' => config('wave.path', 'wave'),
    'as' => 'wave.',
    'middleware' => config('wave.middleware', ['web']),
], function () {
    Route::get('/', WaveConnection::class)->name('connection');

    Route::group([
        'middleware' => [config('wave.auth_middleware', 'auth').':'.config('wave.guard')],
    ], function () {
        Route::get('presence-channel-users', [PresenceChannelUsersController::class, 'index'])->name('presence-channel-users');
        Route::post('presence-channel-users', [PresenceChannelUsersController::class, 'store']);
        Route::delete('presence-channel-users', [PresenceChannelUsersController::class, 'destroy']);

        Route::post('whisper', SendWhisper::class)->name('whisper');
    });
});
