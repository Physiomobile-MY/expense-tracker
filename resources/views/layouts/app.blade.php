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
            ['label' => 'CCC', 'href' => route('ccc.dashboard'), 'active' => request()->routeIs('ccc.dashboard')],
            ['label' => 'Cashflow', 'href' => route('ccc.cashflow'), 'active' => request()->routeIs('ccc.cashflow')],
            ['label' => 'Creditors', 'href' => route('ccc.creditors'), 'active' => request()->routeIs('ccc.creditors') || request()->routeIs('ccc.debts')],
            ['label' => 'Plans', 'href' => route('ccc.payment-plans'), 'active' => request()->routeIs('ccc.payment-plans')],
            ['label' => 'SOA', 'href' => route('ccc.soa'), 'active' => request()->routeIs('ccc.soa')],
            ['label' => 'Bank Recon', 'href' => route('ccc.bank-reconciliation'), 'active' => request()->routeIs('ccc.bank-reconciliation')],
            ['label' => 'Upload', 'href' => route('receipts.create'), 'active' => request()->routeIs('receipts.create')],
            ['label' => 'Approvals', 'href' => route('records.index', ['status' => 'pending_review']), 'active' => request('status') === 'pending_review'],
            ['label' => 'All Records', 'href' => route('records.index'), 'active' => request()->routeIs('records.*')],
            ['label' => 'Users', 'href' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*')],
            ['label' => 'Reports', 'href' => route('ccc.reports'), 'active' => request()->routeIs('ccc.reports')],
            ['label' => 'Settings', 'href' => route('ccc.settings'), 'active' => request()->routeIs('ccc.settings') || request()->routeIs('admin.settings.*')],
            ['label' => 'Audit', 'href' => route('admin.audit-logs.index'), 'active' => request()->routeIs('admin.audit-logs.*')],
        ];
    } elseif ($user?->isFinance()) {
        $navItems = [
            ['label' => 'CCC', 'href' => route('ccc.dashboard'), 'active' => request()->routeIs('ccc.dashboard')],
            ['label' => 'Cashflow', 'href' => route('ccc.cashflow'), 'active' => request()->routeIs('ccc.cashflow')],
            ['label' => 'Transactions', 'href' => route('ccc.transactions'), 'active' => request()->routeIs('ccc.transactions')],
            ['label' => 'Creditors', 'href' => route('ccc.creditors'), 'active' => request()->routeIs('ccc.creditors') || request()->routeIs('ccc.debts')],
            ['label' => 'Plans', 'href' => route('ccc.payment-plans'), 'active' => request()->routeIs('ccc.payment-plans')],
            ['label' => 'SOA', 'href' => route('ccc.soa'), 'active' => request()->routeIs('ccc.soa')],
            ['label' => 'Bank Recon', 'href' => route('ccc.bank-reconciliation'), 'active' => request()->routeIs('ccc.bank-reconciliation')],
            ['label' => 'Comms', 'href' => route('ccc.communication-logs'), 'active' => request()->routeIs('ccc.communication-logs')],
            ['label' => 'Reports', 'href' => route('ccc.reports'), 'active' => request()->routeIs('ccc.reports')],
        ];
    } elseif ($user?->isManagementViewer()) {
        $navItems = [
            ['label' => 'CCC', 'href' => route('ccc.dashboard'), 'active' => request()->routeIs('ccc.dashboard')],
            ['label' => 'Creditors', 'href' => route('ccc.creditors'), 'active' => request()->routeIs('ccc.creditors') || request()->routeIs('ccc.debts')],
            ['label' => 'Plans', 'href' => route('ccc.payment-plans'), 'active' => request()->routeIs('ccc.payment-plans')],
            ['label' => 'SOA', 'href' => route('ccc.soa'), 'active' => request()->routeIs('ccc.soa')],
            ['label' => 'Reports', 'href' => route('ccc.reports'), 'active' => request()->routeIs('ccc.reports')],
            ['label' => 'Profile', 'href' => route('profile.edit'), 'active' => request()->routeIs('profile.*')],
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
    <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur md:hidden">
        <div class="flex items-center justify-between gap-2 px-3 py-3 sm:gap-4 sm:px-6">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                <span class="flex h-10 w-28 shrink-0 items-center sm:w-36">
                    <img src="{{ asset('images/physiomobile-logo.png') }}" alt="Physiomobile" class="max-h-10 w-full object-contain object-left">
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-bold text-gray-900 sm:text-base">{{ config('expenseflow.brand.name') }}</span>
                    <span class="block truncate text-xs text-gray-500">{{ config('expenseflow.brand.tagline') }}</span>
                </span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="pm-btn-secondary !px-3 !py-2" type="submit">Logout</button>
            </form>
        </div>
    </header>

    <aside class="fixed inset-y-0 left-0 z-40 hidden w-56 border-r border-gray-200 bg-white md:flex md:flex-col">
        <div class="border-b border-gray-100 px-4 py-4">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <span class="flex h-8 w-32 shrink-0 items-center">
                    <img src="{{ asset('images/physiomobile-logo.png') }}" alt="Physiomobile" class="max-h-10 w-full object-contain object-left">
                </span>
            </a>
            <div class="mt-3">
                <p class="truncate text-sm font-bold text-gray-950">{{ config('expenseflow.brand.name') }}</p>
                <p class="mt-0.5 text-xs text-gray-500">{{ config('expenseflow.brand.tagline') }}</p>
            </div>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-2.5 py-3">
            @foreach ($navItems as $item)
                <a href="{{ $item['href'] }}" class="group flex items-center justify-between rounded-lg px-3 py-2 text-sm font-semibold transition {{ $item['active'] ? 'bg-[#FDECEC] text-[#A80F16]' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-950' }}">
                    <span>{{ $item['label'] }}</span>
                    @if ($item['active'])
                        <span class="h-2 w-2 rounded-full bg-[#D71920]"></span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="border-t border-gray-100 p-3">
            <div class="mb-2 rounded-lg bg-gray-50 px-3 py-2.5">
                <p class="truncate text-sm font-bold text-gray-950">{{ $user?->name }}</p>
                <p class="mt-0.5 truncate text-xs text-gray-500">{{ $user?->roleLabel() }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="pm-btn-secondary w-full !px-3 !py-2" type="submit">Logout</button>
            </form>
        </div>
    </aside>

    <main class="px-4 py-5 sm:px-6 md:ml-56 md:px-7">
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

<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white md:hidden">
    <div class="flex gap-1 overflow-x-auto px-2 py-2">
        @foreach ($navItems as $item)
            <a href="{{ $item['href'] }}" class="min-w-[4.8rem] flex-1 rounded-lg px-2 py-2 text-center text-xs font-semibold {{ $item['active'] ? 'bg-[#D71920] text-white' : 'text-gray-600' }}">{{ $item['label'] }}</a>
        @endforeach
    </div>
</nav>
</body>
</html>
