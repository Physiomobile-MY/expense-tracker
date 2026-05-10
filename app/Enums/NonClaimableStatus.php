<?php

namespace App\Enums;

enum NonClaimableStatus: string
{
    case Draft = 'Draft';
    case Recorded = 'Recorded';
    case Reviewed = 'Reviewed';
    case Flagged = 'Flagged';
    case Archived = 'Archived';
}
