<?php

use Illuminate\Http\Request;
use App\Http\Controllers\EvaluationCriterionController;
use App\Http\Controllers\SubscriptionPlanController;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function() {
    // API para listas dinâmicas em Vue
    Route::get('/evaluation-criteria', [EvaluationCriterionController::class, 'index']);
    Route::get('/plans', [SubscriptionPlanController::class, 'index']);
});
