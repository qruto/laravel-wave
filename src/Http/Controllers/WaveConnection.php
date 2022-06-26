<?php

namespace Qruto\LaravelWave\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Qruto\LaravelWave\Http\Middleware\GenerateSseSocketId;
use Qruto\LaravelWave\Sse\ServerSentEventStream;

class WaveConnection extends Controller
{
    public function __construct()
    {
        $this->middleware(GenerateSseSocketId::class);
    }

    public function __invoke(Request $request, ServerSentEventStream $responseFactory)
    {
        return $responseFactory->toResponse($request);
    }
}
