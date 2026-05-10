<?php

namespace App\Http\Controllers;

use App\Exports\ExpenseRecordsExport;
use App\Models\Department;
use App\Models\ExpenseCategory;
use App\Models\ExpenseRecord;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canManageExpenses(), 403);

        $query = $this->query($request);

        return view('reports.index', [
            'records' => (clone $query)->latest()->paginate(20)->withQueryString(),
            'summary' => [
                'count' => (clone $query)->count(),
                'amount' => (clone $query)->sum('total_amount'),
                'claimable' => (clone $query)->where('record_type', ExpenseRecord::TYPE_CLAIMABLE)->sum('total_amount'),
                'non_claimable' => (clone $query)->where('record_type', ExpenseRecord::TYPE_NON_CLAIMABLE)->sum('total_amount'),
            ],
            'departments' => Department::where('status', 'active')->orderBy('name')->get(),
            'categories' => ExpenseCategory::where('status', 'active')->orderBy('name')->get(),
            'staff' => User::where('role', 'staff')->orderBy('name')->get(),
        ]);
    }

    public function export(Request $request)
    {
        abort_unless($request->user()->canManageExpenses(), 403);

        $format = $request->validate([
            'format' => ['required', 'in:xlsx,csv,pdf'],
        ])['format'];

        $records = $this->query($request)
            ->with(['user', 'department', 'category', 'approvals.approver'])
            ->latest()
            ->get();

        $filename = 'physiomobile-expenses-'.now()->format('Ymd-His');

        if ($format === 'pdf') {
            return Pdf::loadView('reports.pdf', ['records' => $records])->download($filename.'.pdf');
        }

        return Excel::download(
            new ExpenseRecordsExport($records),
            $filename.'.'.$format,
            $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
        );
    }

    private function query(Request $request): Builder
    {
        return ExpenseRecord::query()
            ->with(['user', 'department', 'category'])
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('receipt_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('receipt_date', '<=', $request->date_to))
            ->when($request->filled('staff_id'), fn ($query) => $query->where('user_id', $request->staff_id))
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->department_id))
            ->when($request->filled('expense_category_id'), fn ($query) => $query->where('expense_category_id', $request->expense_category_id))
            ->when($request->filled('record_type'), fn ($query) => $query->where('record_type', $request->record_type))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', 'like', '%'.$request->payment_method.'%'));
    }
}
