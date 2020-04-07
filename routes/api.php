<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// Email Verification
Auth::routes(['verify' => true]);

// Authentication based requests (api/auth/*)
Route::group([
    'prefix' => 'auth'
], function () {

    // Login/register
    Route::post('register', 'Auth\AuthController@register')->name('register');
    Route::post('login', 'Auth\AuthController@login')->name('login');

    // Get using headers
    Route::group([
      'middleware' => 'auth:api'
    ], function() {
        Route::get('user', 'Auth\AuthController@user')->name('auth.currentuser');
        Route::get('logout', 'Auth\AuthController@logout')->name('auth.logout');
    });
    
    Route::get('email/resend', 'Auth\VerificationController@resend')->name('verification.resend');
    Route::get('email/verify/{id}/{hash}', 'Auth\VerificationController@verify')->name('verification.verify');
});

// User requests (api/users/)
Route::group([
    'prefix' => 'users'
], function () {
    Route::get('', 'UserController@index')->name('users');
    Route::post('edit/{id}', 'UserController@update')->name('user.edit');
    Route::delete('delete/{id}', 'UserController@destroy')->name('user.delete');
    Route::get('{id}', 'UserController@show')->name('user');
});