@extends('layouts.app')

@section('content')
<div class="mb-5">
    <p class="text-sm font-semibold text-[#D71920]">Administration</p>
    <h1 class="text-2xl font-bold text-gray-950">User Management</h1>
</div>

<section class="pm-card mb-4 p-4">
    <h2 class="font-bold text-gray-950">Create User</h2>
    <form method="POST" action="{{ route('admin.users.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @csrf
        <input class="pm-input" name="name" placeholder="Name" required>
        <input class="pm-input" name="email" type="email" placeholder="Email" required>
        <input class="pm-input" name="phone" placeholder="Phone">
        <select class="pm-input" name="department_id">
            <option value="">Department</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}">{{ $department->name }}</option>
            @endforeach
        </select>
        <select class="pm-input" name="role" required>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <select class="pm-input" name="status" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        <input class="pm-input" name="password" type="password" placeholder="Password" required>
        <button class="pm-btn-primary" type="submit">Create</button>
    </form>
</section>

<section class="pm-card overflow-hidden">
    <div class="divide-y divide-gray-100">
        @foreach ($users as $member)
            <form method="POST" action="{{ route('admin.users.update', $member) }}" class="grid gap-3 px-4 py-4 lg:grid-cols-[1fr_1fr_1fr_1fr_1fr_1fr_auto]">
                @csrf
                @method('PUT')
                <input class="pm-input" name="name" value="{{ $member->name }}" required>
                <input class="pm-input bg-gray-50" value="{{ $member->email }}" disabled>
                <input class="pm-input" name="phone" value="{{ $member->phone }}">
                <select class="pm-input" name="department_id">
                    <option value="">Department</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected($member->department_id === $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                <select class="pm-input" name="role">
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected($member->role === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select class="pm-input" name="status">
                    <option value="active" @selected($member->status === 'active')>Active</option>
                    <option value="inactive" @selected($member->status === 'inactive')>Inactive</option>
                </select>
                <div class="flex gap-2">
                    <input class="pm-input max-w-36" name="password" type="password" placeholder="New password">
                    <button class="pm-btn-secondary" type="submit">Save</button>
                </div>
            </form>
            @if (auth()->user()->isDirector() && !$member->isDirector() && $member->id !== auth()->user()->id && !session('impersonating_user_id'))
                <form method="POST" action="{{ route('admin.impersonate.start', $member) }}" class="flex shrink-0 items-center px-4 pb-4 lg:pb-0 lg:pr-4 lg:pt-0">
                    @csrf
                    <button type="submit" class="pm-btn-secondary !py-2 text-xs whitespace-nowrap" onclick="return confirm('Impersonate {{ addslashes($member->name) }}? You will act as this user until you stop.')">
                        Impersonate
                    </button>
                </form>
            @endif
        @endforeach
    </div>
    <div class="border-t border-gray-100 px-4 py-3">{{ $users->links() }}</div>
</section>
@endsection
