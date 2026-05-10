<?php

namespace App\Http\Controllers;

use App\Models\ExpenseRecord;
use App\Services\ExpenseRecordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseWorkflowController extends Controller
{
    public function approve(Request $request, ExpenseRecord $record, ExpenseRecordService $records): RedirectResponse
    {
        $this->authorizeReviewer($request);
        $records->approve($record, $request->user(), $this->remarks($request));

        return back()->with('status', 'Claim approved.');
    }

    public function reject(Request $request, ExpenseRecord $record, ExpenseRecordService $records): RedirectResponse
    {
        $this->authorizeReviewer($request);
        $records->reject($record, $request->user(), $this->remarks($request));

        return back()->with('status', 'Claim rejected.');
    }

    public function clarify(Request $request, ExpenseRecord $record, ExpenseRecordService $records): RedirectResponse
    {
        $this->authorizeReviewer($request);
        $validated = $request->validate(['remarks' => ['required', 'string', 'max:2000']]);
        $records->requestClarification($record, $request->user(), $validated['remarks']);

        return back()->with('status', 'Clarification requested.');
    }

    public function paid(Request $request, ExpenseRecord $record, ExpenseRecordService $records): RedirectResponse
    {
        $this->authorizeReviewer($request);
        $records->markPaid($record, $request->user(), $this->remarks($request));

        return back()->with('status', 'Claim marked as paid.');
    }

    public function review(Request $request, ExpenseRecord $record, ExpenseRecordService $records): RedirectResponse
    {
        $this->authorizeReviewer($request);
        $records->reviewNonClaimable($record, $request->user(), $this->remarks($request));

        return back()->with('status', 'Receipt reviewed.');
    }

    public function flag(Request $request, ExpenseRecord $record, ExpenseRecordService $records): RedirectResponse
    {
        $this->authorizeReviewer($request);
        $records->flagNonClaimable($record, $request->user(), $this->remarks($request));

        return back()->with('status', 'Receipt flagged.');
    }

    public function voidRecord(Request $request, ExpenseRecord $record, ExpenseRecordService $records): RedirectResponse
    {
        abort_unless($record->canBeVoidedBy($request->user()), 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $records->voidRecord($record, $request->user(), $validated['reason']);

        return redirect()->route('records.show', $record)->with('status', 'Claim voided.');
    }

    private function authorizeReviewer(Request $request): void
    {
        abort_unless($request->user()->canManageExpenses(), 403);
    }

    private function remarks(Request $request): ?string
    {
        return $request->validate([
            'remarks' => ['nullable', 'string', 'max:2000'],
        ])['remarks'] ?? null;
    }
}
