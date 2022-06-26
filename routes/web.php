<?php

use Illuminate\Support\Facades\Route;
use Qruto\LaravelWave\Http\Controllers\PresenceChannelUsersController;
use Qruto\LaravelWave\Http\Controllers\SendWhisper;
use Qruto\LaravelWave\Http\Controllers\WaveConnection;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('wave', WaveConnection::class);

    // TODO: name
    Route::post('presence-channel-users', [PresenceChannelUsersController::class, 'store']);
    Route::delete('presence-channel-users', [PresenceChannelUsersController::class, 'destroy']);

    Route::post('whisper', SendWhisper::class);
});
