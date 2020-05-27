<?php

namespace App\Http\Controllers\Route;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RouteController extends Controller
{
    public function hello(Request $request) {
        return response('Hello!', Response::HTTP_OK);
    }

    public function notFound(Request $request) {
        return response()->json([
            'message' => 'The given API route was not found',
            'is_secure' => $request->secure(),
            'user_agent' => $request->userAgent(),
            'timestamp' => Carbon::now(),
            'path' => $request->fullUrl(),
            'method' => $request->method()
        ], Response::HTTP_NOT_FOUND);
    }

    public function notImplemented(Request $request) {
        return response('Nothing here yet...', Response::HTTP_NOT_IMPLEMENTED);
    }

    public function teapot(Request $request) {
        return response('I\'m a teapot', Response::HTTP_I_AM_A_TEAPOT);
    }
}
