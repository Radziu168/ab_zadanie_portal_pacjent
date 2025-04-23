<?php

use App\Http\Controllers\ResultsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);
Route::middleware('auth:api')->get('/results', [ResultsController::class, 'getResults']);
