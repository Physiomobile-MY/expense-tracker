<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExpenseRecordsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Collection $records,
    ) {}

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'Reference No',
            'Record Type',
            'Staff Name',
            'Department',
            'Merchant',
            'Receipt Date',
            'Category',
            'Amount',
            'Payment Method',
            'Status',
            'Submitted Date',
            'Approved Date',
            'Paid Date',
            'Recorded Date',
            'Approver',
            'Remarks',
        ];
    }

    public function map($record): array
    {
        $approval = $record->approvals->where('action', 'approved')->last();

        return [
            $record->claim_reference_no,
            $record->recordTypeLabel(),
            $record->user?->name,
            $record->department?->name,
            $record->merchant_name,
            $record->receipt_date?->format('Y-m-d'),
            $record->category?->name,
            $record->total_amount,
            $record->payment_method,
            $record->statusLabel(),
            $record->submitted_at?->format('Y-m-d H:i'),
            $record->approved_at?->format('Y-m-d H:i'),
            $record->paid_at?->format('Y-m-d H:i'),
            $record->recorded_at?->format('Y-m-d H:i'),
            $approval?->approver?->name,
            $record->remarks,
        ];
    }
}
