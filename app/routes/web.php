<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/branding/logo',[WorkspaceController::class,'logo'])->middleware('throttle:60,1');

Route::middleware('guest')->group(function () {
    Route::view('/login','auth.login')->name('login');
    Route::post('/login',[AuthController::class,'login'])->middleware('throttle:15,1');
});
Route::middleware(['auth','auth.session','throttle:180,1'])->group(function () {
    Route::get('/',[WorkspaceController::class,'dashboard'])->name('dashboard');
    Route::post('/logout',[AuthController::class,'logout']);
    Route::view('/profile','profile');
    Route::view('/help','help');
    Route::put('/profile/password',[AuthController::class,'password'])->middleware('throttle:5,1');
    Route::get('/settings',[WorkspaceController::class,'settings']);
    Route::put('/settings',[WorkspaceController::class,'saveSettings']);
    Route::get('/reports',[WorkspaceController::class,'reports']);
    Route::get('/audit',[WorkspaceController::class,'audit']);
    Route::get('/documents/{id}/download',[WorkspaceController::class,'download'])->whereNumber('id');
    Route::get('/manage/{module}',[WorkspaceController::class,'index']);
    Route::get('/manage/{module}/create',[WorkspaceController::class,'form']);
    Route::get('/manage/{module}/{id}/edit',[WorkspaceController::class,'form'])->whereNumber('id');
    Route::get('/manage/{module}/{id}',[WorkspaceController::class,'show'])->whereNumber('id');
    Route::post('/manage/{module}',[WorkspaceController::class,'save']);
    Route::put('/manage/{module}/{id}',[WorkspaceController::class,'save'])->whereNumber('id');
    Route::delete('/manage/{module}/{id}',[WorkspaceController::class,'destroy'])->whereNumber('id');
    Route::post('/manage/{module}/{id}/approval',[WorkspaceController::class,'approve'])->whereNumber('id');
});
