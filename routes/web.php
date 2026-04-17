<?php

use App\Http\Controllers\TestController;
use App\Http\Controllers\WebPageController;
use Illuminate\Support\Facades\Route;


Route::view('/', 'welcome')->name('home');
Route::prefix('user')->group(function () {
    Route::view('/login', 'login')->name('login');
    Route::view('/register', 'register')->name('register');
});
Route::get('/user/profile', [WebPageController::class, 'profile'])->name('profile');
Route::get('/user/edit', [WebPageController::class, 'profileEdit'])->name('profile.edit');

Route::view('/tests', 'tests.index')->name('tests.index');
Route::view('/tests/create', 'tests.create')->name('tests.create');
Route::get('/tests/{id}', [WebPageController::class, 'testsShow'])->name('tests.show');
Route::get('/tests/{id}/edit', [WebPageController::class, 'testsEdit'])->name('tests.edit');
Route::get('/electronic-attempts/{id}', [WebPageController::class, 'electronicAttemptReview'])->name('tests.electronic-attempt.review');
Route::get('/take-test', [WebPageController::class, 'takeTest'])->name('tests.take');
Route::get('/take-test/session/{token}', [WebPageController::class, 'takeTestSession'])->name('tests.take.session');
Route::get('/take-test/student/{token}', [WebPageController::class, 'takeTestMember'])->name('tests.take.member');

Route::get('/tests/{test}/print', [TestController::class, 'print'])->name('tests.print');
Route::view('/blank-forms/results', 'blank-forms.results')->name('blank-forms.results');
Route::view('/groups', 'groups.index')->name('groups.index');
Route::get('/groups/{id}', [WebPageController::class, 'groupsShow'])->name('groups.show');
