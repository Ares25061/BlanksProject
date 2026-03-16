<?php

use App\Http\Controllers\TestController;
use App\Http\Controllers\BlankFormController;
use Illuminate\Support\Facades\Route;

// Защищенные маршруты (требуют аутентификации)
Route::middleware('auth:api')->group(function () {
    // Тесты
    Route::apiResource('tests', TestController::class);
    Route::post('tests/{test}/questions', [TestController::class, 'addQuestion']);

    // Бланки
    Route::get('blank-forms', [BlankFormController::class, 'index']);
    Route::post('tests/{test}/generate-blank-forms', [BlankFormController::class, 'generateForTest']);
    Route::get('blank-forms/{blank_form}', [BlankFormController::class, 'show']);
    Route::post('blank-forms/{blank_form}/submit', [BlankFormController::class, 'submitAnswers']);
    Route::post('blank-forms/{blank_form}/check', [BlankFormController::class, 'check']);
    Route::post('blank-forms/check-multiple', [BlankFormController::class, 'checkMultiple']);
    Route::get('blank-forms/{blank_form}/grade', [BlankFormController::class, 'getGrade']);
});
