<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\ExpenseCategory;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = [
            'expense.view_all',
            'expense.view_own',
            'expense.create',
            'expense.review',
            'expense.approve',
            'expense.reject',
            'expense.mark_paid',
            'expense.export',
            'settings.manage',
            'users.manage',
            'audit.view',
            'ai_logs.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $director = Role::firstOrCreate(['name' => 'director_super_admin', 'guard_name' => 'web']);
        $finance = Role::firstOrCreate(['name' => 'admin_finance', 'guard_name' => 'web']);
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $director->syncPermissions($permissions);
        $finance->syncPermissions([
            'expense.view_all',
            'expense.review',
            'expense.approve',
            'expense.reject',
            'expense.mark_paid',
            'expense.export',
            'ai_logs.view',
        ]);
        $staff->syncPermissions([
            'expense.view_own',
            'expense.create',
        ]);

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

        foreach (config('expenseflow.category_keywords') as $category => $keywords) {
            ExpenseCategory::updateOrCreate(
                ['code' => str($category)->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->toString()],
                ['name' => $category, 'description' => null, 'keywords' => $keywords, 'status' => 'active']
            );
        }

        $management = Department::where('code', 'MGT')->first();

        $users = [
            [
                'name' => 'Nidzam Yatimi',
                'email' => 'nidzamyatimi@physiomobile.com',
                'role' => 'director_super_admin',
                'department_id' => $management?->id,
            ],
            [
                'name' => 'Saiful',
                'email' => 'saiful@physiomobile.com',
                'role' => 'director_super_admin',
                'department_id' => $management?->id,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData + [
                    'status' => 'active',
                    'must_change_password' => true,
                    'password' => Hash::make('password'),
                ]
            );

            $user->syncRoles([$userData['role']]);
        }

        User::whereIn('email', [
            'director@physiomobile.com',
            'finance@physiomobile.com',
            'staff@physiomobile.com',
        ])->update(['status' => 'inactive']);

        SystemSetting::updateOrCreate(['key' => 'openai'], [
            'value' => [
                'enabled' => (bool) config('services.openai.receipt_extraction_enabled'),
                'model' => config('services.openai.receipt_model'),
                'daily_scan_limit' => (int) config('services.openai.daily_scan_limit'),
            ],
        ]);

        SystemSetting::updateOrCreate(['key' => 'claims'], [
            'value' => [
                'mileage_rate' => (float) config('expenseflow.mileage.default_rate', 0.50),
            ],
        ]);
    }
}
