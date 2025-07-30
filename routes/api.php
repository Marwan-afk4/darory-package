<?php

use App\Http\Controllers\Api\Auth\AuthenticationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::name('api.')->group(function () {
    Route::post('/login', [AuthenticationController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthenticationController::class, 'logout'])->name('logout');
    });
});
