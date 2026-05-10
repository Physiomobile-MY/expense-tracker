<?php

namespace App\Services;

class DuplicateReceiptService
{
    public function isPossibleDuplicate(array $payload): bool
    {
        $required = ['user_id', 'merchant_name', 'receipt_date', 'total_amount', 'receipt_number', 'record_type'];

        foreach ($required as $field) {
            if (! array_key_exists($field, $payload)) {
                return false;
            }
        }

        return true;
    }
}
