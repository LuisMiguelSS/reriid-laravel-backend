<?php

use Illuminate\Support\Facades\Route;

// Not Found
Route::fallback('Route\RouteController@notFound')->name('notfound');

// EasterEgg
Route::any('wp-admin', 'Route\RouteController@teapot')->name('teapot');
Route::get('email/verify/{id}/{hash}', 'Auth\VerificationController@verify')->name('verification.verify');