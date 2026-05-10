<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AIExtractionLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AIExtractionLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canManageExpenses(), 403);

        $logs = AIExtractionLog::with('expenseRecord.user')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.ai-logs.index', compact('logs'));
    }
}
