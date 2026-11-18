<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'RouteRequestController@index')->name('index');
Route::post('/', 'RouteRequestController@store')->name('store');
Route::post('/{id}/cancel', 'RouteRequestController@cancel')->name('cancel');
Route::get('/airports/search', 'RouteRequestController@searchAirports')->name('airports.search');
Route::get('/local-cache-bundle', 'RouteRequestController@localCacheBundle')->name('cache.bundle');
