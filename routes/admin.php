<?php

use App\Http\Controllers\Admin\ApprovalFlowController;
use App\Http\Controllers\Admin\ApprovalStepController;
use App\Http\Controllers\Admin\DivisionController;
use App\Http\Controllers\Admin\MemoTypeController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// NOTE: gabungkan blok ini ke routes/web.php, atau require dari sana:
// require __DIR__.'/admin.php';
// Middleware 'admin' didaftarkan sebagai alias EnsureUserIsAdmin (lihat README).

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('divisions', DivisionController::class)->except(['show']);
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('memo-types', MemoTypeController::class)->except(['show']);

    Route::resource('approval-flows', ApprovalFlowController::class)->except(['show']);
    Route::post('approval-flows/{approvalFlow}/steps', [ApprovalStepController::class, 'store'])
        ->name('approval-flows.steps.store');
    Route::delete('approval-flows/{approvalFlow}/steps/{step}', [ApprovalStepController::class, 'destroy'])
        ->name('approval-flows.steps.destroy');
    Route::post('approval-flows/{approvalFlow}/steps/{step}/move', [ApprovalStepController::class, 'move'])
        ->name('approval-flows.steps.move');
});
