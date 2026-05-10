<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\ExpenseCategory;
use Illuminate\Console\Command;

class EnsureCatalogCommand extends Command
{
    protected $signature = 'expenseflow:ensure-catalog';

    protected $description = 'Create the default Physiomobile departments and expense categories.';

    public function handle(): int
    {
        $departments = [
            ['name' => 'Management', 'code' => 'MGT'],
            ['name' => 'Finance', 'code' => 'FIN'],
            ['name' => 'HR & Admin', 'code' => 'HRA'],
            ['name' => 'Operations', 'code' => 'OPS'],
            ['name' => 'Customer Support', 'code' => 'CS'],
            ['name' => 'Marketing', 'code' => 'MKT'],
            ['name' => 'Sales', 'code' => 'SAL'],
            ['name' => 'Clinical', 'code' => 'CLI'],
            ['name' => 'Technology', 'code' => 'TEC'],
            ['name' => 'Corporate Wellness', 'code' => 'CW'],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(['code' => $department['code']], $department + ['status' => 'active']);
        }

        $categories = [
            'Travel',
            'Petrol',
            'Mileage',
            'Parking',
            'Toll',
            'Meal',
            'Accommodation',
            'Office Supplies',
            'Clinic Supplies',
            'Medical Supplies',
            'Equipment',
            'Training',
            'Software Subscription',
            'Marketing',
            'Corporate Event',
            'Client Entertainment',
            'Maintenance',
            'Utilities',
            'Internet / Telco',
            'Courier / Delivery',
            'Others',
        ];

        foreach ($categories as $category) {
            ExpenseCategory::updateOrCreate(
                ['code' => str($category)->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->toString()],
                ['name' => $category, 'description' => null, 'status' => 'active']
            );
        }

        $this->info(count($departments).' departments and '.count($categories).' categories are ready.');

        return self::SUCCESS;
    }
}
