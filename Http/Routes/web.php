<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', 'RouteRequestController@index')->name('index');
Route::post('/', 'RouteRequestController@store')->name('store');
Route::post('/{id}/cancel', 'RouteRequestController@cancel')->name('cancel');
Route::get('/airports/search', 'RouteRequestController@searchAirports')->name('airports.search');
Route::get('/local-cache-bundle', 'RouteRequestController@localCacheBundle')->name('cache.bundle');
Route::post('/verify-range-override', 'RouteRequestController@verifyRangeOverride')->name('verify_range');
Route::post('/travel/options', 'RouteRequestController@travelOptions')->name('travel.options');
Route::post('/travel/execute', 'RouteRequestController@executeTravel')->name('travel.execute');
Route::post('/travel/request-admin-move', 'RouteRequestController@requestAdminMove')->name('travel.request_admin_move');
Route::get('/vatsim-atc', 'RouteRequestController@vatsimAtcOverlay')->name('vatsim_atc');
Route::post('/{id}/upvote', 'RouteRequestController@toggleUpvote')->name('upvote');
