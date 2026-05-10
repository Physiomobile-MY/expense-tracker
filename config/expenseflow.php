<?php

return [
    'brand' => [
        'name' => 'Physiomobile ExpenseFlow',
        'tagline' => 'Physiomobile - Enriching Quality of Life',
        'primary' => env('APP_THEME_PRIMARY', '#D71920'),
    ],

    'roles' => [
        'director_super_admin' => 'Director Super Admin',
        'admin_finance' => 'Admin / Finance',
        'staff' => 'Staff',
    ],

    'claimable_statuses' => [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'pending_review' => 'Pending Review',
        'need_clarification' => 'Need Clarification',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'paid' => 'Paid',
    ],

    'non_claimable_statuses' => [
        'draft' => 'Draft',
        'recorded' => 'Recorded',
        'reviewed' => 'Reviewed',
        'flagged' => 'Flagged',
        'archived' => 'Archived',
    ],

    'receipt_prompt' => <<<'PROMPT'
You are a receipt extraction assistant for Physiomobile's internal expense management system.

Analyze the uploaded receipt image or PDF and extract the information into valid JSON only.

Rules:
- Return JSON only.
- Do not include markdown.
- If a field is not visible, return null.
- Currency should default to MYR if the receipt appears to be from Malaysia.
- total_amount must be the final paid amount if visible.
- receipt_date must be in YYYY-MM-DD format if possible.
- confidence_score should be between 0 and 1.
- Include all visible receipt line items.
- If the image is blurry, incomplete, or unclear, mention it in notes.
- Do not guess aggressively.
- Do not decide whether the receipt is claimable or non-claimable. The user will decide.
PROMPT,
];
