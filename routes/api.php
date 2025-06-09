<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CreateComponentController;
use App\Http\Controllers\PropertyEvaluationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/create-file', [CreateComponentController::class, 'createFile']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('properties/{property}')->group(function () {
        Route::post('evaluations', [PropertyEvaluationController::class, 'apiStore']);
        Route::put('evaluations/{evaluation}', [PropertyEvaluationController::class, 'apiUpdate']);
    });
});
