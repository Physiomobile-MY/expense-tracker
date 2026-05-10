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

        $categories = config('expenseflow.category_keywords');

        foreach ($categories as $category => $keywords) {
            ExpenseCategory::updateOrCreate(
                ['code' => str($category)->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->toString()],
                ['name' => $category, 'description' => null, 'keywords' => $keywords, 'status' => 'active']
            );
        }

        $this->info(count($departments).' departments and '.count($categories).' categories are ready.');

        return self::SUCCESS;
    }
}
