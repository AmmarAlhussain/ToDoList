<?php

use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Auth;

Route::get('/', [HomeController::class, 'indexPage'])->name('home');

Route::post('/logout', function () {
    Auth::logout();
    return redirect()->route('login');
})->name('logout');

Route::middleware('guest')->group(function () {

    Route::get('/login', [LoginController::class, 'loginPage'])->name('login');

    Route::post('/login', [LoginController::class, 'handleLogin'])->name('handleLogin');

    Route::get('/registration', [RegisterController::class, 'RegisterPage'])->name('register');

    Route::post('/registration', [RegisterController::class, 'handleRegister'])->name('handleRegister');

});

Route::middleware('auth')->group(function () {

    Route::get('/tasks/add', [TaskController::class, 'addTaskPage'])->name('addTaskPage');


    Route::post('/tasks/add', [TaskController::class, 'addTask'])->name('addTask');


    Route::get('/tasks/{task}/edit', [TaskController::class, 'updateTaskPage'])->name('updateTaskPage');


    Route::put('/tasks/{task}/update', [TaskController::class, 'updateTask'])->name('updateTask');


    Route::delete('/task/{task}', [TaskController::class, 'destroy'])->name('delete');
});