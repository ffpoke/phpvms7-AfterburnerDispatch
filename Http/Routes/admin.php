<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', 'AdminController@index')->name('index');
Route::get('/airports/search', 'AdminController@searchAirports')->name('airports.search');
Route::get('/local-cache-bundle', 'AdminController@localCacheBundle')->name('cache.bundle');
Route::post('/subfleet-sync/preview', 'AdminController@previewSubfleetSync')->name('subfleet_sync.preview');
Route::post('/subfleet-sync/execute', 'AdminController@executeSubfleetSync')->name('subfleet_sync.execute');
Route::post('/subfleet-sync/undo', 'AdminController@undoSubfleetSync')->name('subfleet_sync.undo');
Route::post('/subfleet-sync/presets/save', 'AdminController@saveSyncPreset')->name('subfleet_sync.presets.save');
Route::delete('/subfleet-sync/presets/{id}', 'AdminController@deleteSyncPreset')->name('subfleet_sync.presets.delete');
Route::post('/time-fix/preview', 'AdminController@previewFlightTimeFix')->name('time_fix.preview');
Route::post('/time-fix/execute', 'AdminController@executeFlightTimeFix')->name('time_fix.execute');
Route::post('/settings/save', 'AdminController@saveModuleSettings')->name('settings.save');
Route::post('/settings/test-discord', 'AdminController@testDiscordWebhooks')->name('settings.test_discord');
Route::post('/license/save', 'AdminController@saveLicenseKey')->name('license.save');
Route::post('/license/claim-free', 'AdminController@claimFreeLicense')->name('license.claim_free');
Route::post('/license/claim-trial', 'AdminController@claimExecutiveTrial')->name('license.claim_trial');
Route::post('/license/checkout', 'AdminController@startCheckout')->name('license.checkout');
Route::post('/check-update', 'AdminController@checkUpdate')->name('check_update');
Route::post('/install-update', 'AdminController@installUpdate')->name('install_update');
Route::post('/install-paxstudio-update', 'AdminController@installPaxStudioUpdate')->name('install_paxstudio_update');
Route::post('/{id}/parse-fa', 'AdminController@parseFlightAware')->name('parse_fa');
Route::post('/{id}/quick-generate', 'AdminController@quickGenerate')->name('quick_generate');
Route::post('/{id}/mark-completed', 'AdminController@markCompleted')->name('mark_completed');
Route::post('/{id}/status', 'AdminController@updateStatus')->name('status');
Route::post('/{id}/generate-flight', 'AdminController@generateFlight')->name('generate_flight');
Route::delete('/{id}', 'AdminController@destroy')->name('destroy');
