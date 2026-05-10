@extends('layouts.app')

@section('content')
<div class="mb-5">
    <p class="text-sm font-semibold text-[#D71920]">Notifications</p>
    <h1 class="text-2xl font-bold text-gray-950">Updates</h1>
</div>

<section class="pm-card overflow-hidden">
    <div class="divide-y divide-gray-100">
        @forelse ($notifications as $notification)
            <div class="px-4 py-4 {{ $notification->read_at ? 'bg-white' : 'bg-[#FDECEC]/40' }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-bold text-gray-950">{{ $notification->title }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ $notification->message }}</p>
                        <p class="mt-2 text-xs text-gray-400">{{ $notification->created_at->format('Y-m-d H:i') }}</p>
                    </div>
                    @if (! $notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                            @csrf
                            <button class="pm-btn-secondary !px-3 !py-2" type="submit">Read</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-4 py-10 text-center text-sm text-gray-500">No notifications.</div>
        @endforelse
    </div>
    <div class="border-t border-gray-100 px-4 py-3">
        {{ $notifications->links() }}
    </div>
</section>
@endsection
