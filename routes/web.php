<?php

use App\Models\Test;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


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

Route::get('/tests/{id}/print', function ($id) {
    $test = Test::with('questions.answers')->findOrFail($id);
    return view('tests.print', ['test' => $test]);
})->name('tests.print');


