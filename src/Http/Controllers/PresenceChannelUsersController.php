<?php

namespace Qruto\LaravelWave\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Str;
use Qruto\LaravelWave\Events\PresenceChannelJoinEvent;
use Qruto\LaravelWave\Events\PresenceChannelLeaveEvent;
use Qruto\LaravelWave\PresenceChannelUsersRepository;

class PresenceChannelUsersController extends Controller
{
    public function __construct(protected PresenceChannelUsersRepository $repository)
    {
        $this->middleware(function ($request, $next) {
            Broadcast::auth($request);

            return $next($request);
        });
    }

    public function store(Request $request)
    {
        if ($this->repository->join($request->channel_name, $request->user(), $request->header('X-Socket-Id'))) {
            broadcast(new PresenceChannelJoinEvent($request->user(), Str::after($request->channel_name, 'presence-')))->toOthers();
        }

        return response()->json($this->repository->getUsers($request->channel_name));
    }

    public function destroy(Request $request)
    {
        if ($this->repository->leave($request->channel_name, auth()->user(), request()->header('X-Socket-Id'))) {
            broadcast(new PresenceChannelLeaveEvent($request->user(), Str::after($request->channel_name, 'presence-')))->toOthers();
        }

        return response()->json($this->repository->getUsers($request->channel_name));
    }
}
