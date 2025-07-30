<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
};

// 🔐 Authentication Routes
Route::controller(AuthController::class)->group(function () {
    Route::get('/login', 'showLoginForm')->name('login');
    Route::post('/login', 'login')->name('do.login');
    Route::post('/logout', 'logout')->middleware('auth:sanctum')->name('logout');
});