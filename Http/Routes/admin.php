<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'AdminController@index')->name('index');
Route::get('/airports/search', 'AdminController@searchAirports')->name('airports.search');
Route::get('/local-cache-bundle', 'AdminController@localCacheBundle')->name('cache.bundle');
Route::post('/check-update', 'AdminController@checkUpdate')->name('check_update');
Route::post('/install-update', 'AdminController@installUpdate')->name('install_update');
Route::post('/{id}/parse-fa', 'AdminController@parseFlightAware')->name('parse_fa');
Route::post('/{id}/quick-generate', 'AdminController@quickGenerate')->name('quick_generate');
Route::post('/{id}/status', 'AdminController@updateStatus')->name('status');
Route::post('/{id}/generate-flight', 'AdminController@generateFlight')->name('generate_flight');
Route::delete('/{id}', 'AdminController@destroy')->name('destroy');
