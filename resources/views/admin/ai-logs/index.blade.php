@extends('layouts.app')

@section('content')
<div class="mb-5">
    <p class="text-sm font-semibold text-[#D71920]">OpenAI Usage</p>
    <h1 class="text-2xl font-bold text-gray-950">AI Extraction Logs</h1>
</div>

<section class="pm-card mb-4 p-4">
    <form method="GET" action="{{ route('admin.ai-logs.index') }}" class="flex gap-2">
        <select class="pm-input max-w-xs" name="status">
            <option value="">All statuses</option>
            <option value="completed" @selected(request('status') === 'completed')>Completed</option>
            <option value="failed" @selected(request('status') === 'failed')>Failed</option>
        </select>
        <button class="pm-btn-primary" type="submit">Filter</button>
    </form>
</section>

<section class="pm-card overflow-hidden">
    <div class="divide-y divide-gray-100">
        @forelse ($logs as $log)
            <div class="px-4 py-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="font-bold text-gray-950">#{{ $log->id }} · {{ $log->model ?: 'No model' }}</p>
                        <p class="text-sm text-gray-500">
                            {{ $log->expenseRecord?->claim_reference_no ?: 'Draft record' }}
                            @if ($log->expenseRecord?->user)
                                · {{ $log->expenseRecord->user->name }}
                            @endif
                        </p>
                        @if ($log->error_message)
                            <p class="mt-2 text-sm text-red-700">{{ $log->error_message }}</p>
                        @endif
                    </div>
                    <div class="text-sm sm:text-right">
                        @include('partials.status-badge', ['status' => $log->status, 'label' => str($log->status)->headline()])
                        <p class="mt-2 text-gray-500">{{ $log->created_at->format('Y-m-d H:i') }}</p>
                        <p class="text-gray-500">Tokens {{ number_format((int) $log->token_usage_input) }} / {{ number_format((int) $log->token_usage_output) }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="px-4 py-10 text-center text-sm text-gray-500">No AI logs yet.</div>
        @endforelse
    </div>
    <div class="border-t border-gray-100 px-4 py-3">{{ $logs->links() }}</div>
</section>
@endsection
