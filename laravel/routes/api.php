<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BatchOpenAiRequestController;
use App\Http\Controllers\Api\ChatGptConfigController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\UsageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// For Vue 3 一時token発行のルート
Route::post('/issue-temp-token', [TokenController::class, 'issue']);

// For Vue 3 仮登録のルート[save temporary and send email]
Route::post('/pre-register', [RegistrationController::class, 'sendPreRegisterMail'])
    ->middleware('temp_token');

// For Vue 3 仮登録token確認用のルート
Route::get('/register/confirm', [RegistrationController::class, 'confirmToken']);

// For Vue 3 本登録用のルート
Route::post('/register', [RegistrationController::class, 'register']);

// For Vue 3 ログイン用のルート
Route::post('/login', [AuthController::class, 'login']);

// For Vue 3 ログアウト用
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

// For Vue 3 ChatGPT APIへのリクエスト管理
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/requests', [RequestController::class, 'index']);
    Route::post('/requests', [RequestController::class, 'store']);
    Route::patch('/requests/{id}', [RequestController::class, 'update']);
    Route::delete('/requests/{id}', [RequestController::class, 'destroy']);
    Route::get('/chatgpt/config', [ChatGptConfigController::class, 'index']);
    Route::get('/usage/monthly', [UsageController::class, 'monthly']);
});

// Python Batch向け (X-BATCH-KEY)
Route::prefix('batch')->middleware('batch.key')->group(function () {
    Route::get('/chatgpt/config', [ChatGptConfigController::class, 'indexForBatch']);
    Route::get('/openai-requests/limits/global', [BatchOpenAiRequestController::class, 'globalLimit']);
    Route::post('/openai-requests/claim', [BatchOpenAiRequestController::class, 'claim']);
    Route::get('/openai-requests/{id}/limits/request', [BatchOpenAiRequestController::class, 'requestLimit']);
    Route::post('/openai-requests/{id}/complete', [BatchOpenAiRequestController::class, 'complete']);
    Route::post('/openai-requests/{id}/fail', [BatchOpenAiRequestController::class, 'fail']);
});
