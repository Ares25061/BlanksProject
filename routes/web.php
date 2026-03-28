<?php

use App\Http\Controllers\TestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect('/user/register');
});
Route::group(['prefix' => 'user'], function () {
    Route::get('/login', function () {
        return view('login');
    })->name('login');
    Route::get('/register', function () {
        return view('register');
    })->name('register');
});
Route::get('/user/profile', function ( Request $request ) {
    return view('profile', ['user' => $request->id ?? $request->user()]);
})->name('profile');

Route::get('/tests', function () {
    return view('tests.index');
})->name('tests.index');

Route::get('/tests/create', function () {
    return view('tests.create');
})->name('tests.create');

Route::get('/tests/{id}', function ($id) {
    return view('tests.show', ['id' => $id]);
})->name('tests.show');

Route::get('/tests/{id}/edit', function ($id) {
    return view('tests.edit', ['id' => $id]);
})->name('tests.edit');

Route::get('/tests/{test}/print', [TestController::class, 'print'])->name('tests.print');
Route::get('/blank-forms/results', function () {
    return view('blank-forms.results');
})->name('blank-forms.results');
Route::get('/groups', function () {
    return view('groups.index');
})->name('groups.index');
Route::get('/groups/{id}', function ($id) {
    return view('groups.show', ['id' => $id]);
})->name('groups.show');
