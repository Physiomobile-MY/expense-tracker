<?php

namespace App\Services;

use App\Models\ExpenseRecord;

class DuplicateReceiptService
{
    public function isDuplicate(ExpenseRecord $record): bool
    {
        if (! $record->record_type || ! $record->merchant_name || ! $record->receipt_date || ! $record->total_amount) {
            return false;
        }

        return ExpenseRecord::query()
            ->withoutVoided()
            ->whereKeyNot($record->id)
            ->where('user_id', $record->user_id)
            ->where('record_type', $record->record_type)
            ->whereDate('receipt_date', $record->receipt_date)
            ->where('total_amount', $record->total_amount)
            ->where(function ($query) use ($record): void {
                $query->where('merchant_name', $record->merchant_name);

                if ($record->receipt_number) {
                    $query->orWhere('receipt_number', $record->receipt_number);
                }
            })
            ->exists();
    }

    public function refreshWarning(ExpenseRecord $record): bool
    {
        $duplicate = $this->isDuplicate($record);

        $record->forceFill(['duplicate_warning' => $duplicate])->save();

        return $duplicate;
    }
}
