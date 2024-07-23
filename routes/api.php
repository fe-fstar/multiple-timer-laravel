<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

Route::group(['middleware' => JwtMiddleware::class], function() {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::post('verify', [AuthController::class, 'verify'])->name('verify');
});

Route::group(['middleware' => JwtMiddleware::class, "prefix" => "user"], function() {
    Route::put('update', [UserController::class, 'update'])->name('update');
    Route::delete('delete', [UserController::class, 'delete'])->name('delete');
});

Route::group(["middleware"=>"api"], function() {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');
});