<?php

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group and under the 'api.xxx' subdomain.
|
*/

// Email Verification
Auth::routes(['verify' => true, 'register' => false]);
Route::get('auth/email/verify/{id}/{hash}', 'Auth\VerificationController@verify')->name('verification.verify');

Route::group([
    'middleware' => 'apikey.validate'
], function() {

    // Users
    Route::group(['prefix' => 'users'], function () {
        Route::get('', 'UserController@index')->name('users');
        Route::get('{id}', 'UserController@show')->name('user');
        Route::post('{id}', 'UserController@update')->name('user.edit'); // The edit NEEDS to be POST instead of PUT due to a laravel bug
        Route::delete('{id}', 'UserController@destroy')->name('user.delete');
        Route::post('{id}/restore', 'UserController@restore')->name('user.restore');
    });

    Route::group([
        'prefix' => 'auth',
    ], function () {

        Route::post('register', 'Auth\AuthController@register')->name('register');
        Route::post('email/resend', 'Auth\VerificationController@resend')->name('verification.resend');
        Route::post('login', 'Auth\AuthController@login')->name('login'); // This also needs email verification but it's implemented inside the login function

        // 2.1.1. Routes which need email verification to work.
        Route::group([
            'middleware' => [
                'auth:api',
                'verified'
            ]
        ], function() {
            Route::get('user', 'Auth\AuthController@user')->name('auth.currentuser');
            Route::post('logout', 'Auth\AuthController@logout')->name('auth.logout');

            Route::get('posts/nearby', 'PostController@nearby')->name('posts.nearby');
        });

    });

    // 2.3. Post related routes
    Route::group([
        'prefix' => 'posts',
    ], function () {
        Route::get('', 'PostController@index')->name('posts');
        Route::post('create', 'PostController@store')->name('posts.create');
        Route::get('user/{id}', 'PostController@showuser')->name('userposts');
        Route::get('{id}', 'PostController@show')->name('post');
        Route::post('{id}', 'PostController@update')->name('post.edit'); // The edit NEEDS to be POST instead of PUT due to a laravel bug
        Route::delete('{id}', 'PostController@destroy')->name('post.delete');
        Route::post('{id}/restore', 'PostController@restore')->name('post.restore');
    });

    // 2.4. Deleted data routes
    Route::group(['prefix' => 'deleted'], function () {

        // Users
        Route::group(['prefix' => 'users'], function () {
            Route::get('', 'UserController@indexdeleted')->name('deleted.users');
            Route::post('{id}', 'UserController@showdeleted')->name('deleted.user');
        });

        // Posts
        Route::group(['prefix' => 'posts'], function () {
            Route::get('', 'PostController@indexdeleted')->name('deleted.posts');
            Route::get('{id}', 'PostController@indexdeleted')->name('deleted.post');
        });
    });

});

/**
 * 3. Not Found
 */
Route::fallback(function(Request $request){
    return response()->json([
        'message' => 'The given API route was not found',
        'method' => $request->method(),
        'timestamp' => Carbon::now(),
        'user_agent' => $request->userAgent(),
        'path' => $request->fullUrl()
    ], Response::HTTP_NOT_FOUND);
})->name('notfound');

/**
 * 4. Easter Egg
 */
Route::any('/wp-admin', function() {
    return response('I\'m a teapot', Response::HTTP_I_AM_A_TEAPOT);
});