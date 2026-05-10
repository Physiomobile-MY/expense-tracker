<?php

use App\Http\Controllers\Admin\AIExtractionLogController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseRecordController;
use App\Http\Controllers\ExpenseWorkflowController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptFileController;
use App\Http\Controllers\ReceiptUploadController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/upload', [ReceiptUploadController::class, 'create'])->name('receipts.create');
    Route::post('/upload', [ReceiptUploadController::class, 'store'])->name('receipts.store');
    Route::get('/receipt-files/{expenseReceipt}', ReceiptFileController::class)->name('receipts.file');

    Route::get('/records', [ExpenseRecordController::class, 'index'])->name('records.index');
    Route::get('/records/{record}', [ExpenseRecordController::class, 'show'])->name('records.show');
    Route::get('/records/{record}/edit', [ExpenseRecordController::class, 'edit'])->name('records.edit');
    Route::put('/records/{record}', [ExpenseRecordController::class, 'update'])->name('records.update');
    Route::post('/records/{record}/submit', [ExpenseRecordController::class, 'submit'])->name('records.submit');
    Route::post('/records/{record}/comments', [ExpenseRecordController::class, 'comment'])->name('records.comments.store');

    Route::post('/records/{record}/approve', [ExpenseWorkflowController::class, 'approve'])->name('records.approve');
    Route::post('/records/{record}/reject', [ExpenseWorkflowController::class, 'reject'])->name('records.reject');
    Route::post('/records/{record}/clarify', [ExpenseWorkflowController::class, 'clarify'])->name('records.clarify');
    Route::post('/records/{record}/paid', [ExpenseWorkflowController::class, 'paid'])->name('records.paid');
    Route::post('/records/{record}/review', [ExpenseWorkflowController::class, 'review'])->name('records.review');
    Route::post('/records/{record}/flag', [ExpenseWorkflowController::class, 'flag'])->name('records.flag');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');

        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');

        Route::get('/ai-logs', [AIExtractionLogController::class, 'index'])->name('ai-logs.index');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});
