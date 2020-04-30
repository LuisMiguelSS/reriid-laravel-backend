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

// Unneeded API KEY routes
// Email verification
Route::get('auth/email/verify/{id}/{hash}', 'Auth\VerificationController@verify')->name('verification.verify');

// Routes which NEED an API key
Route::group([
    'middleware' => 'apikey.validate'
], function() {
    
    // Authentication api/auth/
    Route::group([
        'prefix' => 'auth',
    ], function () {

        Route::post('register', 'Auth\AuthController@register')->name('register');
        Route::post('email/resend', 'Auth\VerificationController@resend')->name('verification.resend');

        // Routes which need previous email verification
        Route::group([
            'middleware' => 'verified'
        ], function() {

            Route::post('login', 'Auth\AuthController@login')->name('login');

            // Headers Auth
            Route::group([
            'middleware' => 'auth:api'
            ], function() {
                Route::get('user', 'Auth\AuthController@user')->name('auth.currentuser');
                Route::post('logout', 'Auth\AuthController@logout')->name('auth.logout');
            });
        });

    });

    // USERS api/users/
    Route::group([
        'prefix' => 'users',
    ], function () {
        Route::get('', 'UserController@index')->name('users');
        Route::get('{id}', 'UserController@show')->name('user');
        Route::post('{id}', 'UserController@update')->name('user.edit'); // The edit NEEDS to be POST instead of PUT due to a laravel bug
        Route::delete('{id}', 'UserController@destroy')->name('user.delete');
    });

    // POSTS api/posts/
    Route::group([
        'prefix' => 'posts',
    ], function () {
        Route::get('', 'PostController@index')->name('posts');
        Route::post('create', 'PostController@store')->name('posts.create');
        Route::get('user/{id}', 'PostController@showuser')->name('userposts');
        Route::get('{id}', 'PostController@show')->name('post');
        Route::post('{id}', 'PostController@update')->name('post.edit'); // The edit NEEDS to be POST instead of PUT due to a laravel bug
        Route::delete('{id}', 'PostController@destroy')->name('post.delete');
    });

    // Deleted Data api/deleted
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

    // Restore Data (api/restore)
    Route::group(['prefix' => 'restore'], function () {
        Route::post('user/{id}', 'UserController@restore')->name('restore.user');
        Route::get('post/{id}', 'PostController@restore')->name('restore.post');
    });
});