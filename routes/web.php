<?php

use App\Http\Controllers\Admin\AIExtractionLogController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CccController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseRecordController;
use App\Http\Controllers\ExpenseWorkflowController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptFileController;
use App\Http\Controllers\ReceiptUploadController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/change-password', [PasswordChangeController::class, 'edit'])->name('password.change');
    Route::put('/change-password', [PasswordChangeController::class, 'update'])->name('password.update');
});

Route::middleware(['auth', 'password.changed'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/upload', [ReceiptUploadController::class, 'create'])->name('receipts.create');
    Route::post('/upload', [ReceiptUploadController::class, 'store'])->name('receipts.store');
    Route::get('/receipt-files/{expenseReceipt}', ReceiptFileController::class)->name('receipts.file');

    Route::get('/records', [ExpenseRecordController::class, 'index'])->name('records.index');
    Route::get('/records/{record}', [ExpenseRecordController::class, 'show'])->name('records.show');
    Route::get('/records/{record}/edit', [ExpenseRecordController::class, 'edit'])->name('records.edit');
    Route::put('/records/{record}', [ExpenseRecordController::class, 'update'])->name('records.update');
    Route::post('/records/{record}/submit', [ExpenseRecordController::class, 'submit'])->name('records.submit');
    Route::post('/records/{record}/comments', [ExpenseRecordController::class, 'comment'])->name('records.comments.store');
    Route::post('/records/{record}/receipts', [ExpenseRecordController::class, 'addReceipt'])->name('records.receipts.store');
    Route::patch('/records/{record}/receipts/{receipt}', [ExpenseRecordController::class, 'updateReceipt'])->name('records.receipts.update');
    Route::delete('/records/{record}/receipts/{receipt}', [ExpenseRecordController::class, 'removeReceipt'])->name('records.receipts.destroy');

    Route::post('/records/{record}/approve', [ExpenseWorkflowController::class, 'approve'])->name('records.approve');
    Route::post('/records/{record}/reject', [ExpenseWorkflowController::class, 'reject'])->name('records.reject');
    Route::post('/records/{record}/clarify', [ExpenseWorkflowController::class, 'clarify'])->name('records.clarify');
    Route::post('/records/{record}/paid', [ExpenseWorkflowController::class, 'paid'])->name('records.paid');
    Route::post('/records/{record}/review', [ExpenseWorkflowController::class, 'review'])->name('records.review');
    Route::post('/records/{record}/flag', [ExpenseWorkflowController::class, 'flag'])->name('records.flag');
    Route::post('/records/{record}/void', [ExpenseWorkflowController::class, 'voidRecord'])->name('records.void');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::patch('/reports/bulk-status', [ReportController::class, 'bulkStatus'])->name('reports.bulk-status');

    Route::prefix('ccc')->name('ccc.')->group(function (): void {
        Route::get('/', [CccController::class, 'dashboard'])->name('dashboard');
        Route::get('/cashflow', [CccController::class, 'cashflow'])->name('cashflow');
        Route::post('/cashflow', [CccController::class, 'storeCashflow'])->name('cashflow.store');
        Route::get('/transactions', [CccController::class, 'transactions'])->name('transactions');
        Route::post('/transactions', [CccController::class, 'storeTransaction'])->name('transactions.store');
        Route::get('/creditors', [CccController::class, 'creditors'])->name('creditors');
        Route::post('/creditors', [CccController::class, 'storeCreditor'])->name('creditors.store');
        Route::put('/creditors/{creditor}', [CccController::class, 'updateCreditor'])->name('creditors.update');
        Route::get('/debts', [CccController::class, 'debts'])->name('debts');
        Route::post('/debts', [CccController::class, 'storeDebt'])->name('debts.store');
        Route::get('/payment-plans', [CccController::class, 'paymentPlans'])->name('payment-plans');
        Route::post('/payment-plans', [CccController::class, 'storePaymentPlan'])->name('payment-plans.store');
        Route::post('/payment-plans/{paymentPlan}/paid', [CccController::class, 'markPaymentPlanPaid'])->name('payment-plans.paid');
        Route::get('/soa', [CccController::class, 'soa'])->name('soa');
        Route::get('/bank-reconciliation', [CccController::class, 'bankReconciliation'])->name('bank-reconciliation');
        Route::post('/bank-accounts', [CccController::class, 'storeBankAccount'])->name('bank-accounts.store');
        Route::post('/bank-imports', [CccController::class, 'uploadBankCsv'])->name('bank-imports.store');
        Route::get('/communication-logs', [CccController::class, 'communicationLogs'])->name('communication-logs');
        Route::post('/communication-logs', [CccController::class, 'storeCommunicationLog'])->name('communication-logs.store');
        Route::get('/reports', [CccController::class, 'reports'])->name('reports');
        Route::get('/settings', [CccController::class, 'settings'])->name('settings');
        Route::put('/settings', [CccController::class, 'updateSettings'])->name('settings.update');
    });

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

        Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');
        Route::post('/impersonate/{user}', [ImpersonationController::class, 'start'])->name('impersonate.start');
    });
});
