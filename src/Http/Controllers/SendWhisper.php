<?php

namespace Qruto\LaravelWave\Http\Controllers;

use Illuminate\Http\Request;
use Qruto\LaravelWave\Events\ClientEvent;

class SendWhisper
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'channel_name' => ['required', 'string', 'starts_with:private-,presence-'],
            'event_name' => 'required|string',
            'data' => 'required',
        ]);

        broadcast(new ClientEvent(request()->event_name, request()->channel_name, request('data')))->toOthers();

        return response()->noContent();
    }
}
