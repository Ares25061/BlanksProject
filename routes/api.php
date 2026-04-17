<?php

use App\Http\Controllers\TestController;
use App\Http\Controllers\BlankFormController;
use App\Http\Controllers\ElectronicTestController;
use App\Http\Controllers\ScanPreviewController;
use App\Http\Controllers\StudentGroupController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/user', UserController::class);
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/public/test-code/resolve', [ElectronicTestController::class, 'resolveCode']);
Route::get('/public/electronic-sessions/{token}', [ElectronicTestController::class, 'session']);
Route::get('/public/electronic-members/{token}', [ElectronicTestController::class, 'member']);
Route::post('/public/electronic-sessions/{token}/start', [ElectronicTestController::class, 'startFromSession']);
Route::post('/public/electronic-members/{token}/start', [ElectronicTestController::class, 'startFromMember']);
Route::get('/public/electronic-attempts/{token}', [ElectronicTestController::class, 'showAttempt']);
Route::post('/public/electronic-attempts/{token}/submit', [ElectronicTestController::class, 'submitAttempt']);
Route::post('/public/electronic-attempts/{token}/logs', [ElectronicTestController::class, 'logAttempt']);
// Защищенные маршруты (требуют аутентификации)
Route::middleware('auth:api')->group(function () {
    Route::apiResource('student-groups', StudentGroupController::class);
    Route::get('student-groups/{student_group}/gradebook', [StudentGroupController::class, 'gradebook']);
    Route::put('student-groups/{student_group}/gradebook-entry', [StudentGroupController::class, 'upsertGradebookEntry']);
    Route::get('student-groups/{student_group}/gradebook-export', [StudentGroupController::class, 'exportGradebookMonth']);

    // Тесты
    Route::apiResource('tests', TestController::class)->names('api.tests');
    Route::post('tests/{test}/questions', [TestController::class, 'addQuestion']);
    Route::post('tests/import-questions', [TestController::class, 'importQuestions']);
    Route::get('tests/{test}/export', [TestController::class, 'export']);
    Route::patch('tests/{test}/delivery-mode', [TestController::class, 'updateDeliveryMode']);
    Route::patch('tests/{test}/close', [TestController::class, 'close']);
    Route::get('tests/{test}/electronic-dashboard', [ElectronicTestController::class, 'dashboard']);
    Route::post('tests/{test}/electronic-launch', [ElectronicTestController::class, 'launch']);
    Route::get('electronic-attempts/{attempt}', [ElectronicTestController::class, 'showTeacherAttempt']);
    Route::patch('electronic-attempts/{attempt}/assign-grade', [ElectronicTestController::class, 'assignGrade']);
    Route::post('electronic-attempts/{attempt}/attach-student', [ElectronicTestController::class, 'attachStudent']);

    // Бланки
    Route::get('blank-forms', [BlankFormController::class, 'index']);
    Route::post('tests/{test}/generate-blank-forms', [BlankFormController::class, 'generateForTest']);
    Route::post('tests/{test}/scan-blank-forms', [BlankFormController::class, 'scanForTest']);
    Route::delete('tests/{test}/blank-forms', [BlankFormController::class, 'destroyIssuedForTest']);
    Route::get('blank-forms/{blank_form}', [BlankFormController::class, 'show']);
    Route::patch('blank-forms/{blank_form}/assign-grade', [BlankFormController::class, 'assignGrade']);
    Route::delete('blank-forms/{blank_form}', [BlankFormController::class, 'destroy']);
    Route::get('blank-forms/{blank_form}/scan-image', [BlankFormController::class, 'scanImage']);
    Route::get('scan-previews/{token}', [ScanPreviewController::class, 'show']);
    Route::get('scan-previews/{token}/scan-image', [ScanPreviewController::class, 'scanImage']);
    Route::post('blank-forms/{blank_form}/submit', [BlankFormController::class, 'submitAnswers']);
    Route::post('blank-forms/{blank_form}/check', [BlankFormController::class, 'check']);
    Route::post('blank-forms/check-multiple', [BlankFormController::class, 'checkMultiple']);
    Route::get('blank-forms/{blank_form}/grade', [BlankFormController::class, 'getGrade']);


    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/refresh', [UserController::class, 'refresh']);
    Route::post('/user/edit', [UserController::class, 'edit']);
});
