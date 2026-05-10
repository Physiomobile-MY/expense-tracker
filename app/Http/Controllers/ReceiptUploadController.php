<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessReceiptExtractionJob;
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
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $record = $records->createDraftFromUpload($request->user(), $validated['receipt']);

        ProcessReceiptExtractionJob::dispatchSync($record->id);

        return redirect()
            ->route('records.edit', $record)
            ->with('status', 'Receipt scanned successfully. Please review the details before saving.');
    }
}
