<?php

namespace App\Enums;

enum ClaimableStatus: string
{
    case Draft = 'Draft';
    case Submitted = 'Submitted';
    case PendingReview = 'Pending Review';
    case NeedClarification = 'Need Clarification';
    case Approved = 'Approved';
    case Rejected = 'Rejected';
    case Paid = 'Paid';
}
