<?php

use App\Enums\TokenAbilityEnum;
use App\Http\Controllers\Api\Auth\AuthenticationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::name('api')->group(function () {
    Route::post('/register', [AuthenticationController::class, 'register'])->name('register');
    Route::post('/login', [AuthenticationController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/refresh-token',[AuthenticationController::class, 'refreshToken'])->middleware('ability:'.TokenAbilityEnum::ISSUE_ACCESS_TOKEN->value);

        Route::get('/profile', [AuthenticationController::class, 'profile'])->name('profile');
        Route::post('/logout', [AuthenticationController::class, 'logout'])->name('logout');
    });
});
