<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\StepController;
use App\Http\Controllers\UserController;

Route::group(['middleware' => JwtMiddleware::class, "prefix" => "step"], function() {
    Route::get('{plan_id}', [StepController::class, "getWithPlanId"]);
});

Route::group(['middleware' => JwtMiddleware::class, "prefix" => "plan"], function() {
    Route::get('user', [PlanController::class, "getWithUserId"]);
    Route::get('{id}', [PlanController::class, "getWithPlanId"]);
    Route::post('create', [PlanController::class, "create"]);
    Route::put('update', [PlanController::class, "update"]);
    Route::delete('delete', [PlanController::class, 'delete']);
});

Route::group(['middleware' => JwtMiddleware::class], function() {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::post('verify', [AuthController::class, 'verify'])->name('verify');
});

Route::group(['middleware' => JwtMiddleware::class, "prefix" => "user"], function() {
    Route::put('update', [UserController::class, 'update'])->name('updateUser');
    Route::delete('delete', [UserController::class, 'delete'])->name('deleteUser');
});

Route::group(["middleware"=>"api"], function() {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');
});