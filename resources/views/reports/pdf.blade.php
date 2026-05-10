<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1F2937; }
        h1 { color: #D71920; font-size: 20px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #E5E7EB; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #FDECEC; color: #A80F16; }
    </style>
</head>
<body>
    <h1>{{ config('expenseflow.brand.name') }}</h1>
    <div>{{ config('expenseflow.brand.tagline') }}</div>
    <div>Generated {{ now()->format('Y-m-d H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>Reference No</th>
                <th>Type</th>
                <th>Staff</th>
                <th>Department</th>
                <th>Merchant</th>
                <th>Date</th>
                <th>Category</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $record)
                <tr>
                    <td>{{ $record->claim_reference_no }}</td>
                    <td>{{ $record->recordTypeLabel() }}</td>
                    <td>{{ $record->user?->name }}</td>
                    <td>{{ $record->department?->name }}</td>
                    <td>{{ $record->merchant_name }}</td>
                    <td>{{ $record->receipt_date?->format('Y-m-d') }}</td>
                    <td>{{ $record->category?->name }}</td>
                    <td>MYR {{ number_format((float) $record->total_amount, 2) }}</td>
                    <td>{{ $record->statusLabel() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
