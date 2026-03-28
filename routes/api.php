<?php

use App\Http\Controllers\TestController;
use App\Http\Controllers\BlankFormController;
use App\Http\Controllers\StudentGroupController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/user', UserController::class);
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
// Защищенные маршруты (требуют аутентификации)
Route::middleware('auth:api')->group(function () {
    Route::apiResource('student-groups', StudentGroupController::class);
    Route::get('student-groups/{student_group}/gradebook', [StudentGroupController::class, 'gradebook']);
    Route::put('student-groups/{student_group}/gradebook-entry', [StudentGroupController::class, 'upsertGradebookEntry']);

    // Тесты
    Route::apiResource('tests', TestController::class);
    Route::post('tests/{test}/questions', [TestController::class, 'addQuestion']);

    // Бланки
    Route::get('blank-forms', [BlankFormController::class, 'index']);
    Route::post('tests/{test}/generate-blank-forms', [BlankFormController::class, 'generateForTest']);
    Route::post('tests/{test}/scan-blank-forms', [BlankFormController::class, 'scanForTest']);
    Route::get('blank-forms/{blank_form}', [BlankFormController::class, 'show']);
    Route::patch('blank-forms/{blank_form}/assign-grade', [BlankFormController::class, 'assignGrade']);
    Route::delete('blank-forms/{blank_form}', [BlankFormController::class, 'destroy']);
    Route::get('blank-forms/{blank_form}/scan-image', [BlankFormController::class, 'scanImage']);
    Route::post('blank-forms/{blank_form}/submit', [BlankFormController::class, 'submitAnswers']);
    Route::post('blank-forms/{blank_form}/check', [BlankFormController::class, 'check']);
    Route::post('blank-forms/check-multiple', [BlankFormController::class, 'checkMultiple']);
    Route::get('blank-forms/{blank_form}/grade', [BlankFormController::class, 'getGrade']);


    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/refresh', [UserController::class, 'refresh']);
    Route::post('/user/edit', [UserController::class, 'edit']);
});
