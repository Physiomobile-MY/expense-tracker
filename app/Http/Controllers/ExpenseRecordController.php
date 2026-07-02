<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessReceiptExtractionJob;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\ExpenseCategory;
use App\Models\ExpenseReceipt;
use App\Models\ExpenseRecord;
use App\Services\ExpenseRecordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ExpenseRecordController extends Controller
{
    public function index(Request $request): View
    {
        $records = ExpenseRecord::query()
            ->visibleTo($request->user())
            ->with(['user', 'department', 'category', 'primaryReceipt'])
            ->when($request->filled('record_type'), fn ($query) => $query->where('record_type', $request->record_type))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when(! $request->filled('status'), fn ($query) => $query->withoutVoided())
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->department_id))
            ->when($request->filled('staff_id'), fn ($query) => $query->where('user_id', $request->staff_id))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('receipt_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('receipt_date', '<=', $request->date_to))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('records.index', [
            'records' => $records,
            'departments' => Department::where('status', 'active')->orderBy('name')->get(),
            'categories' => ExpenseCategory::where('status', 'active')->orderBy('name')->get(),
            'statuses' => array_merge(config('expenseflow.claimable_statuses'), config('expenseflow.non_claimable_statuses')),
        ]);
    }

    public function show(Request $request, ExpenseRecord $record): View
    {
        $this->authorizeVisible($request, $record);

        $record->load(['user', 'department', 'category', 'receipts', 'items', 'aiLogs', 'approvals.approver', 'comments.user']);

        return view('records.show', compact('record'));
    }

    public function edit(Request $request, ExpenseRecord $record): View|RedirectResponse
    {
        $this->authorizeVisible($request, $record);

        if (! $record->canBeEditedBy($request->user())) {
            return redirect()->route('records.show', $record)->with('status', 'This expense record is locked for editing.');
        }

        $record->load(['receipts', 'items', 'aiLogs']);

        return view('records.edit', [
            'record' => $record,
            'departments' => Department::where('status', 'active')->orderBy('name')->get(),
            'categories' => ExpenseCategory::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ExpenseRecord $record, ExpenseRecordService $records): RedirectResponse
    {
        $this->authorizeVisible($request, $record);

        if (in_array($request->input('intent'), ['claimable', 'non_claimable'], true)) {
            $request->merge(['record_type' => $request->input('intent')]);
            $records->submit($record, $request->user(), $this->validatedRecordData($request, true));

            return redirect()->route('records.show', $record)->with('status', 'Expense record submitted.');
        }

        $records->updateDraft($record, $request->user(), $this->validatedRecordData($request));

        return redirect()->route('records.edit', $record)->with('status', 'Receipt details saved.');
    }

    public function submit(Request $request, ExpenseRecord $record, ExpenseRecordService $records): RedirectResponse
    {
        $this->authorizeVisible($request, $record);

        $records->submit($record, $request->user(), $this->validatedRecordData($request, true));

        return redirect()->route('records.show', $record)->with('status', 'Expense record submitted.');
    }

    public function comment(Request $request, ExpenseRecord $record): RedirectResponse
    {
        $this->authorizeVisible($request, $record);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        $record->comments()->create([
            'user_id' => $request->user()->id,
            'comment' => $validated['comment'],
        ]);

        if ($record->user_id === $request->user()->id && $record->status === 'need_clarification') {
            $record->forceFill([
                'status' => 'pending_review',
                'submitted_at' => now(),
            ])->save();
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'comment_added',
            'module' => 'expense_records',
            'record_id' => $record->id,
            'new_values' => ['comment' => $validated['comment']],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', 'Comment added.');
    }

    public function addReceipt(Request $request, ExpenseRecord $record, ExpenseRecordService $records): RedirectResponse
    {
        $this->authorizeVisible($request, $record);

        if (! $record->canBeEditedBy($request->user())) {
            return back()->with('error', 'This expense record is locked for editing.');
        }

        $validated = $request->validate([
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,heic,heif,pdf', 'max:10240'],
            'document_type' => ['required', 'in:receipt,waze_screenshot,google_maps_screenshot'],
        ], [], [
            'receipt' => 'receipt file',
            'document_type' => 'document type',
        ]);

        $documentType = in_array($validated['document_type'], [
            ExpenseReceipt::DOCUMENT_TYPE_WAZE_SCREENSHOT,
            ExpenseReceipt::DOCUMENT_TYPE_GOOGLE_MAPS_SCREENSHOT,
        ], true) ? $validated['document_type'] : ExpenseReceipt::DOCUMENT_TYPE_RECEIPT;

        $records->attachReceipt($record, $request->user(), $validated['receipt'], $documentType);
        ProcessReceiptExtractionJob::dispatchSync($record->id);

        return back()->with('status', 'Receipt attached and scanned. Categorization updated below.');
    }

    public function updateReceipt(Request $request, ExpenseRecord $record, ExpenseReceipt $receipt): RedirectResponse
    {
        $this->authorizeVisible($request, $record);
        abort_if($receipt->expense_record_id !== $record->id, 404);

        $validated = $request->validate([
            'document_type' => ['required', 'in:receipt,waze_screenshot,google_maps_screenshot'],
        ]);

        $receipt->update($validated);

        return back()->with('status', 'Receipt type updated.');
    }

    public function removeReceipt(Request $request, ExpenseRecord $record, ExpenseReceipt $receipt): RedirectResponse
    {
        $this->authorizeVisible($request, $record);
        abort_if($receipt->expense_record_id !== $record->id, 404);

        Storage::delete($receipt->file_path);
        $receipt->delete();

        return back()->with('status', 'Receipt removed.');
    }

    private function authorizeVisible(Request $request, ExpenseRecord $record): void
    {
        abort_unless($request->user()->canManageExpenses() || $record->user_id === $request->user()->id, 403);
    }

    private function validatedRecordData(Request $request, bool $submit = false): array
    {
        $rules = [
            'record_type' => [$submit ? 'required' : 'nullable', 'in:claimable,non_claimable'],
            'claim_expense_type' => ['nullable', 'in:receipt,mileage,toll,parking,travel,hotel,medical'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'expense_category_id' => ['nullable', 'exists:expense_categories,id'],
            'merchant_name' => ['nullable', 'string', 'max:255'],
            'merchant_address' => ['nullable', 'string'],
            'receipt_date' => [$submit ? 'required' : 'nullable', 'date'],
            'receipt_time' => ['nullable', 'date_format:H:i'],
            'currency' => ['nullable', 'string', 'size:3'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'service_charge' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => [$submit ? 'required' : 'nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'receipt_number' => ['nullable', 'string', 'max:255'],
            'project_cost_center' => ['nullable', 'string', 'max:255'],
            'route_origin' => ['nullable', 'string', 'max:255'],
            'route_destination' => ['nullable', 'string', 'max:255'],
            'route_summary' => ['nullable', 'string', 'max:255'],
            'route_distance_km' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'route_duration_minutes' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'route_arrival_time' => ['nullable', 'string', 'max:50'],
            'mileage_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'mileage_amount' => ['nullable', 'numeric', 'min:0'],
            'toll_amount' => ['nullable', 'numeric', 'min:0'],
            'toll_entries' => ['nullable', 'array'],
            'toll_entries.*.label' => ['nullable', 'string', 'max:255'],
            'toll_entries.*.amount' => ['nullable', 'numeric', 'min:0'],
            'parking_amount' => ['nullable', 'numeric', 'min:0'],
            'medical_patient_name' => ['nullable', 'string', 'max:255'],
            'medical_relationship' => ['nullable', 'in:self,spouse,child,parent,sibling,other'],
            'medical_diagnosis' => ['nullable', 'string', 'max:255'],
            'medical_doctor_name' => ['nullable', 'string', 'max:255'],
            'medical_consultation_fee' => ['nullable', 'numeric', 'min:0'],
            'medical_medication_fee' => ['nullable', 'numeric', 'min:0'],
            'medical_panel_clinic' => ['nullable', 'boolean'],
            'hotel_check_in_date' => ['nullable', 'date'],
            'hotel_check_out_date' => ['nullable', 'date', 'after_or_equal:hotel_check_in_date'],
            'hotel_check_in_time' => ['nullable', 'string', 'max:10'],
            'hotel_check_out_time' => ['nullable', 'string', 'max:10'],
            'hotel_room_number' => ['nullable', 'string', 'max:50'],
            'hotel_room_type' => ['nullable', 'string', 'max:100'],
            'hotel_num_nights' => ['nullable', 'integer', 'min:1'],
            'hotel_num_adults' => ['nullable', 'integer', 'min:0'],
            'hotel_num_children' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.amount' => ['nullable', 'numeric', 'min:0'],
        ];

        return $request->validate($rules, [], [
            'expense_category_id' => 'expense category',
            'description' => 'purpose / description',
        ]);
    }
}
