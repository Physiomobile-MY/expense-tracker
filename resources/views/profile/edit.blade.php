@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="mb-5">
        <p class="text-sm font-semibold text-[#D71920]">Profile</p>
        <h1 class="text-2xl font-bold text-gray-950">{{ $user->name }}</h1>
    </div>

    <section class="pm-card p-5">
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="pm-label" for="name">Name</label>
                <input class="pm-input" id="name" name="name" value="{{ old('name', $user->name) }}" required>
            </div>
            <div>
                <label class="pm-label" for="email">Email</label>
                <input class="pm-input bg-gray-50" id="email" value="{{ $user->email }}" disabled>
            </div>
            <div>
                <label class="pm-label" for="phone">Phone</label>
                <input class="pm-input" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
            </div>
            <div>
                <label class="pm-label" for="password">New password</label>
                <input class="pm-input" id="password" name="password" type="password">
            </div>
            <div>
                <label class="pm-label" for="password_confirmation">Confirm password</label>
                <input class="pm-input" id="password_confirmation" name="password_confirmation" type="password">
            </div>
            <button class="pm-btn-primary w-full" type="submit">Save Profile</button>
        </form>
    </section>
</div>
@endsection
