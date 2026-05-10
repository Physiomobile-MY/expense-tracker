@extends('layouts.app')

@section('content')
<div class="mb-5">
    <p class="text-sm font-semibold text-[#D71920]">Administration</p>
    <h1 class="text-2xl font-bold text-gray-950">Expense Categories</h1>
</div>

<section class="pm-card mb-4 p-4">
    <form method="POST" action="{{ route('admin.categories.store') }}" class="grid gap-3 lg:grid-cols-[1fr_0.5fr_1fr_0.5fr_auto]">
        @csrf
        <input class="pm-input" name="name" placeholder="Category name" required>
        <input class="pm-input" name="code" placeholder="Code" required>
        <input class="pm-input" name="description" placeholder="Description">
        <select class="pm-input" name="status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        <button class="pm-btn-primary" type="submit">Add</button>
    </form>
</section>

<section class="pm-card overflow-hidden">
    <div class="divide-y divide-gray-100">
        @foreach ($categories as $category)
            <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="grid gap-3 px-4 py-4 lg:grid-cols-[1fr_0.5fr_1fr_0.5fr_auto]">
                @csrf
                @method('PUT')
                <input class="pm-input" name="name" value="{{ $category->name }}" required>
                <input class="pm-input" name="code" value="{{ $category->code }}" required>
                <input class="pm-input" name="description" value="{{ $category->description }}">
                <select class="pm-input" name="status">
                    <option value="active" @selected($category->status === 'active')>Active</option>
                    <option value="inactive" @selected($category->status === 'inactive')>Inactive</option>
                </select>
                <button class="pm-btn-secondary" type="submit">Save</button>
            </form>
        @endforeach
    </div>
    <div class="border-t border-gray-100 px-4 py-3">{{ $categories->links() }}</div>
</section>
@endsection
