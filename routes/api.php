<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// Email Verification
Auth::routes(['verify' => true]);

// Force all routes to use a valid API key
Route::group([
    'middleware' => 'apikey.validate'
], function() {
    
    // Authentication based requests (api/auth/*)
    Route::group([
        'prefix' => 'auth'
    ], function () {

        // Login/register (api/auth/)
        Route::post('register', 'Auth\AuthController@register')->name('register');
        Route::post('login', 'Auth\AuthController@login')->name('login');

        // Headers authentication (api/auth/)
        Route::group([
        'middleware' => 'auth:api'
        ], function() {
            Route::get('user', 'Auth\AuthController@user')->name('auth.currentuser');
            Route::post('logout', 'Auth\AuthController@logout')->name('auth.logout');
        });

        // Email (api/auth/email)
        Route::group([
            'prefix' => 'email'
        ], function() {
            Route::get('resend', 'Auth\VerificationController@resend')->name('verification.resend');
            Route::get('verify/{id}/{hash}', 'Auth\VerificationController@verify')->name('verification.verify');
        });
        
    });

    // User requests (api/users/)
    Route::group([
        'prefix' => 'users',
    ], function () {
        Route::get('', 'UserController@index')->name('users');
        Route::get('{id}', 'UserController@show')->name('user');
        // The edit NEEDS to be POST instead of PUT due to a laravel bug
        Route::post('{id}', 'UserController@update')->name('user.edit');
        Route::delete('{id}', 'UserController@destroy')->name('user.delete');
    });

    // Post requests (api/posts/)
    Route::group([
        'prefix' => 'posts',
    ], function () {
        Route::get('', 'PostController@index')->name('posts');
        Route::post('create', 'PostController@store')->name('posts.create');
        Route::get('user/{id}', 'PostController@showuser')->name('userposts');
        Route::get('{id}', 'PostController@show')->name('post');
        // The edit NEEDS to be POST instead of PUT due to a laravel bug
        Route::post('{id}', 'PostController@update')->name('post.edit');
        Route::delete('{id}', 'PostController@destroy')->name('post.delete');
    });

    // Deleted Data (api/deleted)
    Route::group(['prefix' => 'deleted'], function () {

        // Users
        Route::group(['prefix' => 'users'], function () {
            Route::get('', 'UserController@indexdeleted')->name('deleted.users');
        });

        // Posts
        Route::group(['prefix' => 'posts'], function () {
            Route::get('', 'PostController@indexdeleted')->name('deleted.posts');
        });
    });
});