<?php

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|
| These routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
*/

// Not Found
Route::fallback(function(Request $request){
    return response()->json([
        'message' => 'The given API route was not found',
        'method' => $request->method(),
        'timestamp' => Carbon::now(),
        'user_agent' => $request->userAgent(),
        'path' => $request->fullUrl()
    ], 404);
})->name('notfound');

Auth::routes();

Route::any('/wp-admin', function() {
    return response('I\'m a teapot', 418);
});