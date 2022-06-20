<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Qruto\LaravelWave\Events\ClientEvent;
use Qruto\LaravelWave\Http\Controllers\PresenceChannelUsersController;
use Qruto\LaravelWave\Http\Middleware\GenerateSseSocketId;
use Qruto\LaravelWave\Sse\ServerSentEventStream;

Route::middleware(['web', 'auth'])->group(function () {
    Route::middleware(GenerateSseSocketId::class)->get(
        'wave',
        fn (Request $request, ServerSentEventStream $responseFactory)
            => $responseFactory->toResponse($request)
    );


    // TODO: name
    Route::post('presence-channel-users', [PresenceChannelUsersController::class, 'store']);
    Route::delete('presence-channel-users', [PresenceChannelUsersController::class, 'destroy']);

    Route::post('whisper', function () {
        broadcast(new ClientEvent(request()->event_name, request('data')))->toOthers();

        return response()->noContent();
    });
});
