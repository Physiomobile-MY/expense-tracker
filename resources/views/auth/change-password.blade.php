@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-lg">
    <div class="mb-5">
        <p class="text-sm font-semibold text-[#D71920]">Account Security</p>
        <h1 class="text-2xl font-bold text-gray-950">Change Temporary Password</h1>
        <p class="mt-2 text-sm text-gray-500">Set a new password before using Physiomobile ExpenseFlow.</p>
    </div>

    <section class="pm-card p-5">
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="pm-label" for="password">New password</label>
                <input class="pm-input" id="password" name="password" type="password" required autofocus>
            </div>
            <div>
                <label class="pm-label" for="password_confirmation">Confirm password</label>
                <input class="pm-input" id="password_confirmation" name="password_confirmation" type="password" required>
            </div>
            <button class="pm-btn-primary w-full" type="submit">Change Password</button>
        </form>
    </section>
</div>
@endsection
