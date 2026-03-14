<?php

use Botble\Base\Facades\BaseHelper;
use Botble\RezgoConnector\Http\Controllers\RezgoSettingsController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix'     => BaseHelper::getAdminPrefix() . '/rezgo-connector',
    'middleware' => ['web', 'auth'],
    'as'         => 'rezgo-connector.',
], function () {
    Route::get('settings', [RezgoSettingsController::class, 'index'])
        ->name('settings');

    Route::post('settings', [RezgoSettingsController::class, 'update'])
        ->name('settings.update');

    Route::get('test-connection', [RezgoSettingsController::class, 'testConnection'])
        ->name('test-connection');
});

