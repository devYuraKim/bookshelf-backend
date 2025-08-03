<?php

use App\Http\Controllers\BooksController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\PromptController;
use Illuminate\Support\Facades\Route;


//user
Route::apiResource('user', UserController::class);

//post
Route::apiResource('posts', PostController::class)->only(['index', 'show', 'update', 'store']);
Route::apiResource('user.post', PostController::class)->scoped()->except(['index', 'show']);;

Route::post('/prompt', [PromptController::class, 'submitPrompt']);

Route::get('/auth/google', [GoogleController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);


Route::post('/login', [AuthController::class, 'login']);


Route::get('/auth-restore', [AuthController::class, 'authRestore']);
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});


Route::get('/books', [BooksController::class, 'getSearch']);
