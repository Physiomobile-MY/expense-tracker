<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('expenseflow.brand.name') }} — Reset Password</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAFAFA]">
<main class="flex min-h-screen items-center justify-center px-4 py-8">
    <section class="pm-card w-full max-w-md p-6">
        <div class="mb-7 flex items-center gap-3">
            <div class="flex h-14 w-40 items-center">
                <img src="{{ asset('images/physiomobile-logo.png') }}" alt="Physiomobile" class="max-h-14 w-full object-contain object-left">
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-950">{{ config('expenseflow.brand.name') }}</h1>
                <p class="text-sm text-gray-500">Set a new password</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="pm-label" for="email">Email address</label>
                <input class="pm-input" id="email" name="email" type="email" value="{{ old('email', $email ?? '') }}" required autofocus>
            </div>
            <div>
                <label class="pm-label" for="password">New password</label>
                <input class="pm-input" id="password" name="password" type="password" required autocomplete="new-password">
                <p class="mt-1 text-xs text-gray-400">Minimum 8 characters.</p>
            </div>
            <div>
                <label class="pm-label" for="password_confirmation">Confirm new password</label>
                <input class="pm-input" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
            </div>
            <button class="pm-btn-primary w-full" type="submit">Reset Password</button>
        </form>

        <p class="mt-5 text-center text-sm text-gray-500">
            <a href="{{ route('login') }}" class="font-semibold text-[#D71920] hover:underline">Back to login</a>
        </p>
    </section>
</main>
</body>
</html>
