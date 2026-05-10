<?php

namespace App\Services;

use App\Enums\ExpenseRecordType;

class ExpenseRecordService
{
    public function generateReference(ExpenseRecordType $type, string $yyyymm, int $sequence): string
    {
        $prefix = $type === ExpenseRecordType::Claimable ? 'PMEXP' : 'PMREC';

        return sprintf('%s-%s-%05d', $prefix, $yyyymm, $sequence);
    }
}
