<?php

namespace App\Enums;

enum ExpenseRecordType: string
{
    case Claimable = 'claimable';
    case NonClaimable = 'non_claimable';
}
