<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Qruto\LaravelWave\Events\ClientEvent;
use Qruto\LaravelWave\Events\PresenceChannelJoinEvent;
use Qruto\LaravelWave\Events\PresenceChannelLeaveEvent;
use Qruto\LaravelWave\Http\Middleware\GenerateSseSocketId;
use Qruto\LaravelWave\PresenceChannelUsersRepository;
use Qruto\LaravelWave\Sse\ServerSentEventStream;

Route::middleware(['web', 'auth'])->group(function () {
    Route::middleware(GenerateSseSocketId::class)->get(
        'wave',
        fn (Request $request, ServerSentEventStream $responseFactory)
            => $responseFactory->toResponse($request)
    );


    Route::post('presence-channel-users', function () {
        /** @var \App\PresenceChannelUsersRepository $store */
        $store = app(PresenceChannelUsersRepository::class);

        if ($store->join(request()->channel, auth()->user(), request()->header('X-Socket-Id'))) {
            broadcast(new PresenceChannelJoinEvent(auth()->user()))->toOthers();
        }

        return response()->json($store->getUsers(request()->channel));
    });

    Route::delete('presence-channel-users', function () {
        /** @var \App\PresenceChannelUsersRepository $store */
        $store = app(PresenceChannelUsersRepository::class);

        if ($store->leave(request()->channel, auth()->user(), request()->header('X-Socket-Id'))) {
            broadcast(new PresenceChannelLeaveEvent(auth()->user()))->toOthers();
        }

        return response()->json($store->getUsers(request()->channel));
    });

    Route::post('whisper', function () {
        broadcast(new ClientEvent(request()->event_name, request('data')))->toOthers();

        return response()->noContent();
    });
});
