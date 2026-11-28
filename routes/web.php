<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemoActionController;
use App\Http\Controllers\MemoController;
use App\Http\Controllers\MemoPdfController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::get('public/memos/{memo}/pdf', [MemoPdfController::class, 'showPublic'])
    ->middleware('signed')
    ->name('memos.public-pdf');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('memos', MemoController::class);
    Route::post('memos/{memo}/submit', [MemoActionController::class, 'submit'])->name('memos.submit');
    Route::post('memos/{memo}/approve', [MemoActionController::class, 'approve'])->name('memos.approve');
    Route::post('memos/{memo}/deny', [MemoActionController::class, 'deny'])->name('memos.deny');
    Route::post('memos/{memo}/disburse', [MemoActionController::class, 'disburse'])->name('memos.disburse');
    Route::post('memos/{memo}/realization', [MemoActionController::class, 'realization'])->name('memos.realization');
    Route::get('memos/{memo}/pdf', MemoPdfController::class)->name('memos.pdf');
    Route::get('memos/{memo}/pdf/download', [MemoPdfController::class, 'download'])->name('memos.pdf.download');
    Route::get('memos/{memo}/attachments/{attachment}/preview', [AttachmentController::class, 'preview'])
        ->scopeBindings()->name('memos.attachments.preview');
    Route::get('memos/{memo}/attachments/{attachment}', [AttachmentController::class, 'download'])
        ->scopeBindings()->name('memos.attachments.download');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::get('notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');

    Route::middleware(EnsureRole::class.':administrator')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::post('users/{user}/test-email', [UserController::class, 'testEmail'])->name('users.test-email');
    });
});
