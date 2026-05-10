<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('expenseflow.brand.name') }} Login</title>
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
                <p class="text-sm text-gray-500">{{ config('expenseflow.brand.tagline') }}</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="pm-label" for="email">Email</label>
                <input class="pm-input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div>
                <label class="pm-label" for="password">Password</label>
                <input class="pm-input" id="password" name="password" type="password" required>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-[#D71920] focus:ring-[#D71920]">
                Remember me
            </label>
            <button class="pm-btn-primary w-full" type="submit">Login</button>
        </form>

        <div class="mt-6 rounded-lg bg-[#FDECEC] p-3 text-xs text-[#A80F16]">
            Seeded MVP accounts use password <span class="font-semibold">password</span>: director@physiomobile.com, finance@physiomobile.com, staff@physiomobile.com
        </div>
    </section>
</main>
</body>
</html>
