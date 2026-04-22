<?php

use Illuminate\Support\Facades\Route;
use MobtakerSystem\SsoClient\Http\Controllers\SsoAuthController;

Route::middleware(['web'])->group(function () {
    // SSO Login routes
    Route::get(config('sso-client.endpoints.login'), [SsoAuthController::class, 'login'])
        ->name('sso.login');

    Route::get(config('sso-client.endpoints.callback'), [SsoAuthController::class, 'callback'])
        ->name('sso.callback');

    Route::post(config('sso-client.endpoints.logout'), [SsoAuthController::class, 'logout'])
        ->name('sso.logout')
        ->middleware('auth');

    Route::post(config('sso-client.endpoints.refresh'), [SsoAuthController::class, 'refresh'])
        ->name('sso.refresh')
        ->middleware('auth');
});
