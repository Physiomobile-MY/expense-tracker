<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('expenseflow.brand.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen antialiased">
@php
    $user = auth()->user();
    $navItems = [];

    if ($user?->isDirector()) {
        $navItems = [
            ['label' => 'Dashboard', 'href' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
            ['label' => 'Upload', 'href' => route('receipts.create'), 'active' => request()->routeIs('receipts.create')],
            ['label' => 'Approvals', 'href' => route('records.index', ['status' => 'pending_review']), 'active' => request('status') === 'pending_review'],
            ['label' => 'All Records', 'href' => route('records.index'), 'active' => request()->routeIs('records.*')],
            ['label' => 'Users', 'href' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*')],
            ['label' => 'Reports', 'href' => route('reports.index'), 'active' => request()->routeIs('reports.*')],
            ['label' => 'Settings', 'href' => route('admin.settings.index'), 'active' => request()->routeIs('admin.settings.*')],
            ['label' => 'Audit', 'href' => route('admin.audit-logs.index'), 'active' => request()->routeIs('admin.audit-logs.*')],
        ];
    } elseif ($user?->isFinance()) {
        $navItems = [
            ['label' => 'Dashboard', 'href' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
            ['label' => 'Claims', 'href' => route('records.index', ['record_type' => 'claimable']), 'active' => request('record_type') === 'claimable'],
            ['label' => 'Receipts', 'href' => route('records.index', ['record_type' => 'non_claimable']), 'active' => request('record_type') === 'non_claimable'],
            ['label' => 'Reports', 'href' => route('reports.index'), 'active' => request()->routeIs('reports.*')],
            ['label' => 'Settings', 'href' => route('admin.categories.index'), 'active' => request()->routeIs('admin.categories.*')],
        ];
    } else {
        $navItems = [
            ['label' => 'Dashboard', 'href' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
            ['label' => 'Upload', 'href' => route('receipts.create'), 'active' => request()->routeIs('receipts.create')],
            ['label' => 'My Records', 'href' => route('records.index'), 'active' => request()->routeIs('records.*')],
            ['label' => 'Notifications', 'href' => route('notifications.index'), 'active' => request()->routeIs('notifications.*')],
            ['label' => 'Profile', 'href' => route('profile.edit'), 'active' => request()->routeIs('profile.*')],
        ];
    }
@endphp

<div class="min-h-screen pb-24 lg:pb-0">
    <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#D71920] text-sm font-bold text-white">PM</span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-bold text-gray-900 sm:text-base">{{ config('expenseflow.brand.name') }}</span>
                    <span class="block truncate text-xs text-gray-500">{{ config('expenseflow.brand.tagline') }}</span>
                </span>
            </a>
            <div class="flex items-center gap-3">
                <span class="hidden text-right sm:block">
                    <span class="block text-sm font-semibold text-gray-900">{{ $user?->name }}</span>
                    <span class="block text-xs text-gray-500">{{ $user?->roleLabel() }}</span>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="pm-btn-secondary !px-3 !py-2" type="submit">Logout</button>
                </form>
            </div>
        </div>
        <nav class="hidden border-t border-gray-100 lg:block">
            <div class="mx-auto flex max-w-7xl gap-1 px-4 py-2 sm:px-6 lg:px-8">
                @foreach ($navItems as $item)
                    <a href="{{ $item['href'] }}" class="rounded-lg px-3 py-2 text-sm font-semibold {{ $item['active'] ? 'bg-[#FDECEC] text-[#A80F16]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">{{ $item['label'] }}</a>
                @endforeach
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <div class="font-semibold">Please check the highlighted fields.</div>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white lg:hidden">
    <div class="flex gap-1 overflow-x-auto px-2 py-2">
        @foreach ($navItems as $item)
            <a href="{{ $item['href'] }}" class="min-w-[4.8rem] flex-1 rounded-lg px-2 py-2 text-center text-xs font-semibold {{ $item['active'] ? 'bg-[#D71920] text-white' : 'text-gray-600' }}">{{ $item['label'] }}</a>
        @endforeach
    </div>
</nav>
</body>
</html>
