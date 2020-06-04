<?php

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

// API Throttling measures
Route::group(['middleware' => 'api'], function () {
    Route::any('/', 'Route\RouteController@hello')->name('internal.alive');

    // Email Verification
    Auth::routes(['register' => false]);

    // User token aunthentication NOT required
    Route::group(['prefix' => 'auth'], function () {
        Route::post('register', 'Auth\AuthController@register')->name('register');
        Route::post('email/resend', 'Auth\VerificationController@resend')->name('verification.resend');
        Route::post('login', 'Auth\AuthController@login')->name('login'); // This also needs email verification but it's implemented inside the login function
    });


    // User token authentication required
    Route::group([
        'middleware' => [
            'auth:api',
            'email.verified'
        ]
    ], function () {

    // Users
    Route::group(['prefix' => 'users'], function () {
        Route::get('', 'UserController@index')->name('users');
        Route::get('deleted', 'UserController@indexdeleted')->name('users.deleted');
        Route::get('deleted/{id}', 'UserController@showdeleted')->name('deleted.user');
        Route::get('{id}', 'UserController@show')->name('user');
        Route::post('{id}', 'UserController@update')->name('user.edit'); // The edit NEEDS to be POST instead of PUT due to a laravel bug
        Route::delete('{id}', 'UserController@destroy')->name('user.delete');
        Route::post('{id}/restore', 'UserController@restore')->name('user.restore');
    });

    Route::group([
        'prefix' => 'auth',
    ], function () {
        Route::get('user', 'Auth\AuthController@user')->name('auth.currentuser');
        Route::post('logout', 'Auth\AuthController@logout')->name('auth.logout');
        Route::get('posts/nearby', 'PostController@nearby')->name('posts.nearby');
    });

    // Posts
    Route::group([
        'prefix' => 'posts',
    ], function () {
        Route::get('', 'PostController@index')->name('posts');
        Route::get('deleted', 'PostController@indexdeleted')->name('posts.deleted');
        Route::post('create', 'PostController@store')->name('posts.create');
        Route::get('deleted/{id}', 'PostController@indexdeleted')->name('post.deleted');
        Route::get('user/{id}', 'PostController@showuser')->name('userposts');
        Route::get('{id}', 'PostController@show')->name('post');
        Route::post('{id}', 'PostController@update')->name('post.edit'); // The edit NEEDS to be POST instead of PUT due to a laravel bug
        Route::delete('{id}', 'PostController@destroy')->name('post.delete');
        Route::post('{id}/restore', 'PostController@restore')->name('post.restore');
    });

});

});

// Custom throttling measures
Route::group([
    'middleware' => 'throttle:300,1',
    'prefix' => 'check'
], function () {
    Route::post('username/{username}', 'Auth\FieldController@usernameExists')->name('username.exists');
    Route::post('email/{email}', 'Auth\FieldController@emailExists')->name('email.exists');
    Route::post('user/{emailOrUsername}', 'Auth\FieldController@userExists')->name('user.exists');
});