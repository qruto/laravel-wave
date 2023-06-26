<?php

namespace Qruto\LaravelWave\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Str;
use Qruto\LaravelWave\Events\PresenceChannelJoinEvent;
use Qruto\LaravelWave\Events\PresenceChannelLeaveEvent;
use Qruto\LaravelWave\Storage\PresenceChannelUsersRepository;

class PresenceChannelUsersController extends Controller
{
    protected array $userInfo;

    public function __construct(protected PresenceChannelUsersRepository $repository)
    {
        $this->middleware((function ($request, $next) {
            $this->userInfo = json_decode(Broadcast::auth($request), true, 512, JSON_THROW_ON_ERROR)['channel_data']['user_info'];

            return $next($request);
        }));
    }

    public function store(Request $request)
    {
        if ($this->repository->join($request->channel_name, $request->user(), $this->userInfo, $request->header('X-Socket-Id'))) {
            broadcast(new PresenceChannelJoinEvent($this->userInfo, Str::after($request->channel_name, 'presence-')))->toOthers();
        }

        return response()->json($this->repository->getUsers($request->channel_name));
    }

    public function destroy(Request $request)
    {
        if ($this->repository->leave($request->channel_name, $request->user(), $request->header('X-Socket-Id'))) {
            broadcast(new PresenceChannelLeaveEvent($this->userInfo, Str::after($request->channel_name, 'presence-')))->toOthers();
        }

        return response()->json($this->repository->getUsers($request->channel_name));
    }
}
