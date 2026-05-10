@extends('layouts.app')

@section('content')
<div class="mb-5">
    <p class="text-sm font-semibold text-[#D71920]">Governance</p>
    <h1 class="text-2xl font-bold text-gray-950">Audit Logs</h1>
</div>

<section class="pm-card mb-4 p-4">
    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="flex gap-2">
        <input class="pm-input max-w-xs" name="module" value="{{ request('module') }}" placeholder="Module">
        <button class="pm-btn-primary" type="submit">Filter</button>
    </form>
</section>

<section class="pm-card overflow-hidden">
    <div class="divide-y divide-gray-100">
        @forelse ($logs as $log)
            <div class="px-4 py-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="font-bold text-gray-950">{{ str($log->action)->headline() }}</p>
                        <p class="text-sm text-gray-500">{{ $log->module }} #{{ $log->record_id }} · {{ $log->user?->name ?: 'System' }}</p>
                        <p class="mt-2 text-xs text-gray-500">{{ $log->ip_address }} · {{ str($log->user_agent)->limit(120) }}</p>
                    </div>
                    <p class="text-sm text-gray-500">{{ $log->created_at->format('Y-m-d H:i') }}</p>
                </div>
            </div>
        @empty
            <div class="px-4 py-10 text-center text-sm text-gray-500">No audit logs yet.</div>
        @endforelse
    </div>
    <div class="border-t border-gray-100 px-4 py-3">{{ $logs->links() }}</div>
</section>
@endsection
