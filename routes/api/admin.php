<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group and under the 'admin.xxx' subdomain.
|
*/

Route::any('/', 'Route\RouteController@hello')->name('admin.alive');

// User related routes
Route::group(['prefix' => 'users'], function () {

    Route::get('', 'UserController@index')->name('users');
    Route::get('deleted', 'UserController@indexdeleted')->name('users.deleted');
    Route::get('deleted/{id}', 'UserController@showdeleted')->name('user.deleted');
    Route::post('', 'Auth\AuthController@register')->name('user.create');
    Route::post('{id}/send-verification-email', 'Auth\VerificationController@adminresend')->name('verification.resend');
    Route::get('{id}', 'UserController@show')->name('user');
    Route::post('{id}', 'UserController@update')->name('user.edit'); // The edit NEEDS to be POST instead of PUT due to a laravel bug
    Route::delete('{id}', 'UserController@destroy')->name('user.delete');
    Route::delete('{id}/force-delete', 'UserController@harddestroy')->name('user.harddelete');
    Route::get('{id}/posts', 'PostController@showuser')->name('user.posts');
    Route::post('{id}/restore', 'UserController@restore')->name('user.restore');

});

// Posts
Route::group(['prefix' => 'posts'], function () {
    
    Route::get('', 'PostController@index')->name('posts');
    Route::post('', 'PostController@store')->name('posts.create');
    Route::get('deleted', 'PostController@indexdeleted')->name('posts.deleted');
    Route::get('deleted/{id}', 'PostController@showdeleted')->name('post.deleted');
    Route::get('nearby/{id}', 'PostController@nearbyid')->name('posts.nearbyid');
    Route::get('{id}', 'PostController@show')->name('post');
    Route::post('{id}', 'PostController@update')->name('post.edit'); // The edit NEEDS to be POST instead of PUT due to a laravel bug
    Route::delete('{id}', 'PostController@destroy')->name('post.delete');
    Route::delete('{id}/force-delete', 'PostController@harddestroy')->name('post.harddelete');
    Route::post('{id}/restore', 'PostController@restore')->name('post.restore');

});