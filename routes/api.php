<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Http\Controllers\GoogleLoginController;
use App\Http\Controllers\TaskController;

Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/google-login', [GoogleLoginController::class, 'googleLogin']);
Route::post('/tasks', [TaskController::class,'store']);
Route::delete('/tasks/{email}', [TaskController::class, 'destroy']);
Route::put('/tasks/{email}', [TaskController::class, 'update']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

