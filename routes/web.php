<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
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
