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

    'notifications' => [
        'finance_approval_email' => env('FINANCE_APPROVAL_EMAIL', 'finance.hq@physiomobile.com'),
    ],

    'mileage' => [
        'default_rate' => (float) env('EXPENSEFLOW_MILEAGE_RATE', 0.50),
    ],

    'claimable_statuses' => [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'pending_review' => 'Pending Review',
        'need_clarification' => 'Need Clarification',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'paid' => 'Paid',
        'voided' => 'Voided',
    ],

    'non_claimable_statuses' => [
        'draft' => 'Draft',
        'recorded' => 'Recorded',
        'reviewed' => 'Reviewed',
        'flagged' => 'Flagged',
        'archived' => 'Archived',
        'voided' => 'Voided',
    ],

    'category_keywords' => [
        'Travel' => ['grab', 'airasia', 'flight', 'taxi', 'train', 'bus', 'transport', 'travel'],
        'Petrol' => ['petronas', 'shell', 'caltex', 'bhp', 'petrol', 'fuel', 'ron95', 'ron97'],
        'Mileage' => ['mileage', 'waze', 'distance', 'km'],
        'Parking' => ['parking', 'parkir'],
        'Toll' => ['toll', 'tol', 'touch n go', 'smart tag', 'rfid'],
        'Meal' => ['restaurant', 'cafe', 'kopitiam', 'nasi', 'kopi', 'meal', 'food', 'dine', 'ayam', 'makan'],
        'Accommodation' => ['hotel', 'inn', 'homestay', 'accommodation', 'booking'],
        'Office Supplies' => ['stationery', 'paper', 'printer', 'office supplies'],
        'Clinic Supplies' => ['clinic supplies', 'clinic'],
        'Medical Supplies' => ['medical', 'pharmacy', 'guardian', 'watsons', 'medicine'],
        'Equipment' => ['equipment', 'device', 'hardware'],
        'Training' => ['training', 'course', 'workshop', 'seminar'],
        'Software Subscription' => ['software', 'subscription', 'saas', 'google workspace', 'microsoft', 'openai'],
        'Marketing' => ['marketing', 'ads', 'advertising', 'printing', 'banner'],
        'Corporate Event' => ['event', 'corporate event'],
        'Client Entertainment' => ['client entertainment', 'entertainment'],
        'Maintenance' => ['maintenance', 'repair', 'service'],
        'Utilities' => ['utility', 'utilities', 'electric', 'water', 'tnb', 'syabas'],
        'Internet / Telco' => ['internet', 'telco', 'maxis', 'celcom', 'digi', 'umobile', 'unifi'],
        'Courier / Delivery' => ['courier', 'delivery', 'poslaju', 'j&t', 'dhl', 'grab express', 'lalamove'],
        'Others' => [],
    ],

    'receipt_prompt' => <<<'PROMPT'
You are an expense evidence extraction assistant for Physiomobile's internal expense management system.

Analyze the uploaded receipt image, PDF, or Waze route screenshot and extract the information into valid JSON only.

Rules:
- Return JSON only.
- Do not include markdown.
- If a field is not visible, return null.
- Currency should default to MYR if the receipt appears to be from Malaysia.
- total_amount must be the final paid amount if visible.
- receipt_date must be in YYYY-MM-DD format if possible.
- confidence_score should be between 0 and 1.
- Include all visible receipt line items.
- document_type should be "receipt" for normal receipts and "waze_screenshot" for Waze/navigation screenshots.
- claim_category should be one of receipt, mileage, toll, parking, travel, meal, petrol, grab, other.
- For Waze screenshots, extract destination, visible route/via text, distance in km, duration in minutes, ETA/arrival time, and visible toll amount.
- For Waze toll text like "TOLL ~RM 4.60" or "Toll ~ RM1.31", put the amount in route_toll_amount.
- Do not calculate mileage_amount. The system will multiply route_distance_km by the configured mileage rate.
- For parking receipts or parking screenshots, put the paid parking amount in parking_amount.
- If the image is blurry, incomplete, or unclear, mention it in notes.
- Do not guess aggressively.
- Do not decide whether the receipt is claimable or non-claimable. The user will decide.
PROMPT,
];
