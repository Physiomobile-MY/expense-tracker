<?php

namespace App\Console\Commands;

use App\Models\AIExtractionLog;
use App\Models\AuditLog;
use App\Models\ExpenseApproval;
use App\Models\ExpenseComment;
use App\Models\ExpenseNotification;
use App\Models\ExpenseReceipt;
use App\Models\ExpenseReceiptItem;
use App\Models\ExpenseRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClearExpenseRecordsCommand extends Command
{
    protected $signature = 'expenseflow:clear-records {--force : Delete records without confirmation}';

    protected $description = 'Delete all expense records and related test data while preserving users, departments, categories, and settings.';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Delete all expense records, receipts, AI logs, approvals, comments, notifications, and audit logs?')) {
            $this->warn('No records were deleted.');

            return self::FAILURE;
        }

        $receiptPaths = ExpenseReceipt::query()
            ->pluck('file_path')
            ->filter()
            ->values()
            ->all();

        $counts = [
            'expense_records' => ExpenseRecord::withTrashed()->count(),
            'expense_receipts' => ExpenseReceipt::count(),
            'expense_receipt_items' => ExpenseReceiptItem::count(),
            'ai_extraction_logs' => AIExtractionLog::count(),
            'expense_approvals' => ExpenseApproval::count(),
            'expense_comments' => ExpenseComment::count(),
            'expense_notifications' => ExpenseNotification::count(),
            'audit_logs' => AuditLog::count(),
        ];

        DB::transaction(function (): void {
            ExpenseComment::query()->delete();
            ExpenseApproval::query()->delete();
            AIExtractionLog::query()->delete();
            ExpenseReceiptItem::query()->delete();
            ExpenseReceipt::query()->delete();
            ExpenseNotification::query()->delete();
            AuditLog::query()->delete();
            ExpenseRecord::withTrashed()->forceDelete();
        });

        foreach (array_chunk($receiptPaths, 100) as $paths) {
            Storage::delete($paths);
        }

        foreach ($counts as $table => $count) {
            $this->line($table.': '.$count.' deleted');
        }

        $this->info('Expense records and related test data cleared.');

        return self::SUCCESS;
    }
}
