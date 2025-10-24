<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Backward-compatible routes
Route::post('/user', [UserController::class, 'store']);
Route::get('/user', [UserController::class, 'index']);

// Preferred pluralized endpoints
Route::post('/users', [UserController::class, 'store']);
Route::get('/users', [UserController::class, 'index']);
