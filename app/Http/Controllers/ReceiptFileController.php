<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ExpenseReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptFileController extends Controller
{
    public function __invoke(Request $request, ExpenseReceipt $expenseReceipt): StreamedResponse
    {
        $record = $expenseReceipt->expenseRecord;
        abort_unless($request->user()->canManageExpenses() || $record->user_id === $request->user()->id, 403);
        $disk = Storage::disk((string) config('expenseflow.receipt_disk', 'receipts'));
        abort_unless($disk->exists($expenseReceipt->file_path), 404);

        if ($request->user()->canManageExpenses() && $record->user_id !== $request->user()->id) {
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'receipt_downloaded',
                'module' => 'expense_receipts',
                'record_id' => $expenseReceipt->id,
                'new_values' => [
                    'expense_record_id' => $record->id,
                    'downloaded_for_user_id' => $record->user_id,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $disk->response($expenseReceipt->file_path, $expenseReceipt->original_filename);
    }
}
