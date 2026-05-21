<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessReceiptExtractionJob;
use App\Models\ExpenseReceipt;
use App\Services\ExpenseRecordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceiptUploadController extends Controller
{
    public function create(): View
    {
        return view('receipts.upload');
    }

    public function store(Request $request, ExpenseRecordService $records): RedirectResponse
    {
        $validated = $request->validate([
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,heic,heif,pdf', 'max:10240'],
            'document_type' => ['required', 'in:receipt,waze_screenshot,google_maps_screenshot'],
        ], [], [
            'receipt' => 'receipt file',
            'document_type' => 'upload type',
        ]);

        $record = $records->createDraftFromUpload(
            $request->user(),
            $validated['receipt'],
            in_array($validated['document_type'], [
                ExpenseReceipt::DOCUMENT_TYPE_WAZE_SCREENSHOT,
                ExpenseReceipt::DOCUMENT_TYPE_GOOGLE_MAPS_SCREENSHOT,
            ], true) ? $validated['document_type'] : ExpenseReceipt::DOCUMENT_TYPE_RECEIPT
        );

        ProcessReceiptExtractionJob::dispatchSync($record->id);

        return redirect()
            ->route('records.edit', $record)
            ->with('status', 'Upload scanned successfully. Please review the details before saving.');
    }
}
