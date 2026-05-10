@php
    $status = $status ?? 'draft';
    $colors = [
        'draft' => 'bg-gray-100 text-gray-700',
        'submitted' => 'bg-blue-50 text-blue-700',
        'pending_review' => 'bg-amber-50 text-amber-700',
        'need_clarification' => 'bg-orange-50 text-orange-700',
        'approved' => 'bg-green-50 text-green-700',
        'rejected' => 'bg-red-50 text-red-700',
        'paid' => 'bg-emerald-100 text-emerald-800',
        'recorded' => 'bg-sky-50 text-sky-700',
        'reviewed' => 'bg-green-50 text-green-700',
        'flagged' => 'bg-red-50 text-red-700',
        'archived' => 'bg-gray-200 text-gray-700',
    ];
@endphp
<span class="pm-badge {{ $colors[$status] ?? 'bg-gray-100 text-gray-700' }}">{{ $label ?? str($status)->headline() }}</span>
