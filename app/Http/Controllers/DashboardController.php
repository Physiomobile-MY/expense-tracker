<?php

namespace App\Http\Controllers;

use App\Models\AIExtractionLog;
use App\Models\ExpenseRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $base = ExpenseRecord::query()->visibleTo($user);
        $month = (clone $base)->whereBetween('expense_records.created_at', [$monthStart, $monthEnd]);

        $metrics = [
            'claimable_month' => (clone $month)->where('record_type', ExpenseRecord::TYPE_CLAIMABLE)->sum('total_amount'),
            'approved_month' => (clone $month)->where('status', 'approved')->sum('total_amount'),
            'pending_month' => (clone $month)->whereIn('status', ['submitted', 'pending_review', 'need_clarification'])->sum('total_amount'),
            'rejected_month' => (clone $month)->where('status', 'rejected')->sum('total_amount'),
            'non_claimable_count' => (clone $month)->where('record_type', ExpenseRecord::TYPE_NON_CLAIMABLE)->count(),
            'non_claimable_month' => (clone $month)->where('record_type', ExpenseRecord::TYPE_NON_CLAIMABLE)->sum('total_amount'),
            'paid_month' => (clone $month)->where('status', 'paid')->sum('total_amount'),
            'pending_count' => (clone $base)->whereIn('status', ['submitted', 'pending_review', 'need_clarification'])->count(),
            'duplicate_count' => (clone $base)->where('duplicate_warning', true)->count(),
        ];

        $recent = (clone $base)
            ->with(['user', 'department', 'category'])
            ->latest()
            ->limit(8)
            ->get();

        $categoryTotals = collect();
        $departmentTotals = collect();
        $staffTotals = collect();
        $aiUsage = null;
        $highValueClaims = collect();

        if ($user->canManageExpenses()) {
            $categoryTotals = (clone $month)
                ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expense_records.expense_category_id')
                ->select(DB::raw("coalesce(expense_categories.name, 'Uncategorised') as label"), DB::raw('sum(total_amount) as total'))
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            $departmentTotals = (clone $month)
                ->leftJoin('departments', 'departments.id', '=', 'expense_records.department_id')
                ->select(DB::raw("coalesce(departments.name, 'No Department') as label"), DB::raw('sum(total_amount) as total'))
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            $staffTotals = (clone $month)
                ->leftJoin('users', 'users.id', '=', 'expense_records.user_id')
                ->select(DB::raw('users.name as label'), DB::raw('sum(total_amount) as total'))
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            $aiUsage = [
                'scans' => AIExtractionLog::whereDate('created_at', '>=', $monthStart)->count(),
                'failed' => AIExtractionLog::whereDate('created_at', '>=', $monthStart)->where('status', 'failed')->count(),
                'input_tokens' => AIExtractionLog::whereDate('created_at', '>=', $monthStart)->sum('token_usage_input'),
                'output_tokens' => AIExtractionLog::whereDate('created_at', '>=', $monthStart)->sum('token_usage_output'),
            ];

            $highValueClaims = ExpenseRecord::query()
                ->with(['user', 'department'])
                ->claimable()
                ->where('total_amount', '>=', 500)
                ->latest()
                ->limit(5)
                ->get();
        }

        return view('dashboard', compact(
            'user',
            'metrics',
            'recent',
            'categoryTotals',
            'departmentTotals',
            'staffTotals',
            'aiUsage',
            'highValueClaims'
        ));
    }
}
