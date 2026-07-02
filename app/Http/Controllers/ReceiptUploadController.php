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
            'receipts' => ['required', 'array', 'min:1', 'max:10'],
            'receipts.*' => ['required', 'file', 'mimes:jpg,jpeg,png,heic,heif,pdf', 'max:10240'],
            'document_type' => ['required', 'in:receipt,waze_screenshot,google_maps_screenshot'],
        ], [], [
            'receipts' => 'receipt files',
            'receipts.*' => 'receipt file',
            'document_type' => 'upload type',
        ]);

        $documentType = in_array($validated['document_type'], [
            ExpenseReceipt::DOCUMENT_TYPE_WAZE_SCREENSHOT,
            ExpenseReceipt::DOCUMENT_TYPE_GOOGLE_MAPS_SCREENSHOT,
        ], true) ? $validated['document_type'] : ExpenseReceipt::DOCUMENT_TYPE_RECEIPT;

        $files = $validated['receipts'];
        $createdRecords = [];

        foreach ($files as $file) {
            $record = $records->createDraftFromUpload($request->user(), $file, $documentType);
            ProcessReceiptExtractionJob::dispatchSync($record->id);
            $createdRecords[] = $record;
        }

        if (count($createdRecords) === 1) {
            return redirect()
                ->route('records.edit', $createdRecords[0])
                ->with('status', 'Upload scanned successfully. Please review the details before saving.');
        }

        return redirect()
            ->route('records.index')
            ->with('status', count($createdRecords).' receipts uploaded and scanned. Please review each one before submitting.');
    }
}
