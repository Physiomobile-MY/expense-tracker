<?php

namespace App\Http\Controllers;

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

        return Storage::response($expenseReceipt->file_path, $expenseReceipt->original_filename);
    }
}
