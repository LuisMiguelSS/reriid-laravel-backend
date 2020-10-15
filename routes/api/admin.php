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

Route::any('/', 'Route\RouteController@hello')->name('admin.admin.alive');

// User related routes
Route::group(['prefix' => 'users'], function () {

    Route::get('', 'UserController@index')->name('admin.users');
    Route::get('deleted', 'UserController@indexdeleted')->name('admin.users.deleted');
    Route::get('deleted/{id}', 'UserController@showdeleted')->name('admin.user.deleted');
    Route::post('', 'Auth\AuthController@register')->name('admin.user.create');
    Route::post('{id}/send-verification-email', 'Auth\VerificationController@adminresend')->name('admin.verification.resend');
    Route::get('{id}', 'UserController@show')->name('admin.user');
    Route::post('{id}', 'UserController@update')->name('admin.user.edit'); // The edit NEEDS to be POST instead of PUT due to a laravel bug
    Route::delete('{id}', 'UserController@destroy')->name('admin.user.delete');
    Route::delete('{id}/force-delete', 'UserController@harddestroy')->name('admin.user.harddelete');
    Route::get('{id}/posts', 'PostController@showuser')->name('admin.user.posts');
    Route::post('{id}/restore', 'UserController@restore')->name('admin.user.restore');

});

// Posts
Route::group(['prefix' => 'posts'], function () {
    
    Route::get('', 'PostController@index')->name('admin.posts');
    Route::post('', 'PostController@store')->name('admin.posts.create');
    Route::get('deleted', 'PostController@indexdeleted')->name('admin.posts.deleted');
    Route::get('deleted/{id}', 'PostController@showdeleted')->name('admin.post.deleted');
    Route::get('nearby/{id}', 'PostController@nearbyid')->name('admin.posts.nearbyid');
    Route::get('{id}', 'PostController@show')->name('admin.post');
    Route::post('{id}', 'PostController@update')->name('admin.post.edit'); // The edit NEEDS to be POST instead of PUT due to a laravel bug
    Route::delete('{id}', 'PostController@destroy')->name('admin.post.delete');
    Route::delete('{id}/force-delete', 'PostController@harddestroy')->name('admin.post.harddelete');
    Route::post('{id}/restore', 'PostController@restore')->name('admin.post.restore');

});