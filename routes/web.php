<?php

use Illuminate\Support\Facades\Route;

/*
|
| These routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
*/

// Not Found
Route::fallback(function(Illuminate\Http\Request $request){
    return response()->json([
        'message' => 'The given route was not found',
        'method' => $request->method(),
        'user_agent' => $request->userAgent(),
        'requested_path' => $request->fullUrl()
    ], 404);
})->name('notfound');

Auth::routes();

Route::any('/wp-admin', function() {
    return response('I\'m a teapot',418);
});