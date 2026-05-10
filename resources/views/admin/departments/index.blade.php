@extends('layouts.app')

@section('content')
<div class="mb-5">
    <p class="text-sm font-semibold text-[#D71920]">Administration</p>
    <h1 class="text-2xl font-bold text-gray-950">Departments</h1>
</div>

<section class="pm-card mb-4 p-4">
    <form method="POST" action="{{ route('admin.departments.store') }}" class="grid gap-3 sm:grid-cols-[1fr_0.5fr_0.5fr_auto]">
        @csrf
        <input class="pm-input" name="name" placeholder="Department name" required>
        <input class="pm-input" name="code" placeholder="Code" required>
        <select class="pm-input" name="status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        <button class="pm-btn-primary" type="submit">Add</button>
    </form>
</section>

<section class="pm-card overflow-hidden">
    <div class="divide-y divide-gray-100">
        @foreach ($departments as $department)
            <form method="POST" action="{{ route('admin.departments.update', $department) }}" class="grid gap-3 px-4 py-4 sm:grid-cols-[1fr_0.5fr_0.5fr_auto]">
                @csrf
                @method('PUT')
                <input class="pm-input" name="name" value="{{ $department->name }}" required>
                <input class="pm-input" name="code" value="{{ $department->code }}" required>
                <select class="pm-input" name="status">
                    <option value="active" @selected($department->status === 'active')>Active</option>
                    <option value="inactive" @selected($department->status === 'inactive')>Inactive</option>
                </select>
                <button class="pm-btn-secondary" type="submit">Save</button>
            </form>
        @endforeach
    </div>
    <div class="border-t border-gray-100 px-4 py-3">{{ $departments->links() }}</div>
</section>
@endsection
