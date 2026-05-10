<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ config('expenseflow.brand.name') }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1F2937; line-height: 1.5;">
    <h1 style="color: #D71920; font-size: 20px; margin-bottom: 8px;">
        {{ $event === 'approved' ? 'Claim Approved' : 'Claim Pending Approval' }}
    </h1>

    <p>
        {{ $event === 'approved'
            ? 'A claim has been approved and is ready for finance follow-up.'
            : 'A claim has been submitted and is waiting for review.' }}
    </p>

    <table cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 640px;">
        <tr>
            <td style="border: 1px solid #E5E7EB; font-weight: bold;">Reference</td>
            <td style="border: 1px solid #E5E7EB;">{{ $record->claim_reference_no }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #E5E7EB; font-weight: bold;">Staff</td>
            <td style="border: 1px solid #E5E7EB;">{{ $record->user?->name }} &lt;{{ $record->user?->email }}&gt;</td>
        </tr>
        <tr>
            <td style="border: 1px solid #E5E7EB; font-weight: bold;">Department</td>
            <td style="border: 1px solid #E5E7EB;">{{ $record->department?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #E5E7EB; font-weight: bold;">Category</td>
            <td style="border: 1px solid #E5E7EB;">{{ $record->category?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #E5E7EB; font-weight: bold;">Merchant</td>
            <td style="border: 1px solid #E5E7EB;">{{ $record->merchant_name ?? '-' }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #E5E7EB; font-weight: bold;">Receipt Date</td>
            <td style="border: 1px solid #E5E7EB;">{{ $record->receipt_date?->format('Y-m-d') ?? '-' }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #E5E7EB; font-weight: bold;">Amount</td>
            <td style="border: 1px solid #E5E7EB;">{{ $record->currency ?? 'MYR' }} {{ number_format((float) $record->total_amount, 2) }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #E5E7EB; font-weight: bold;">Status</td>
            <td style="border: 1px solid #E5E7EB;">{{ $record->statusLabel() }}</td>
        </tr>
        @if ($actor)
            <tr>
                <td style="border: 1px solid #E5E7EB; font-weight: bold;">Action By</td>
                <td style="border: 1px solid #E5E7EB;">{{ $actor->name }} &lt;{{ $actor->email }}&gt;</td>
            </tr>
        @endif
        @if ($remarks)
            <tr>
                <td style="border: 1px solid #E5E7EB; font-weight: bold;">Remarks</td>
                <td style="border: 1px solid #E5E7EB;">{{ $remarks }}</td>
            </tr>
        @endif
    </table>

    <p style="margin-top: 20px;">
        <a href="{{ route('records.show', $record) }}" style="background: #D71920; color: #FFFFFF; display: inline-block; padding: 10px 14px; text-decoration: none;">
            View Claim
        </a>
    </p>
</body>
</html>
