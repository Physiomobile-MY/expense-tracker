<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('expenseflow.brand.name') }} — Forgot Password</title>
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
                <p class="text-sm text-gray-500">Reset your password</p>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <p class="mb-5 text-sm text-gray-600">Enter your email address and we'll send you a link to reset your password.</p>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label class="pm-label" for="email">Email address</label>
                <input class="pm-input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            </div>
            <button class="pm-btn-primary w-full" type="submit">Send Reset Link</button>
        </form>

        <p class="mt-5 text-center text-sm text-gray-500">
            <a href="{{ route('login') }}" class="font-semibold text-[#D71920] hover:underline">Back to login</a>
        </p>
    </section>
</main>
</body>
</html>
