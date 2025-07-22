<?php

use App\Http\Controllers\BooksController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\PromptController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return response()->json($request->user());
// })->middleware('auth:api');

//user
Route::apiResource('user', UserController::class);

//post
Route::apiResource('posts', PostController::class)->only(['index', 'show', 'update', 'store']);
Route::apiResource('user.post', PostController::class)->scoped()->except(['index', 'show']);;

Route::post('/prompt', [PromptController::class, 'submitPrompt']);

Route::get('/auth/google', [GoogleController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);

Route::post('/login', [LoginController::class, 'login']);
Route::post('/refresh-token', [LoginController::class, 'refreshToken']);

Route::get('/user', [LoginController::class, 'user'])->middleware('auth:api');

Route::get('/books', [BooksController::class, 'getSearch']);
