<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\ExpenseCategory;
use App\Models\BankAccount;
use App\Models\SystemSetting;
use App\Models\TransactionCategory;
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
            'ccc.view',
            'ccc.manage',
            'ccc.settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $director = Role::firstOrCreate(['name' => 'director_super_admin', 'guard_name' => 'web']);
        $finance = Role::firstOrCreate(['name' => 'admin_finance', 'guard_name' => 'web']);
        $managementViewer = Role::firstOrCreate(['name' => 'management_viewer', 'guard_name' => 'web']);
        $executive = Role::firstOrCreate(['name' => 'executive', 'guard_name' => 'web']);
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
            'ccc.view',
            'ccc.manage',
        ]);
        $managementViewer->syncPermissions([
            'ccc.view',
        ]);
        $staff->syncPermissions([
            'expense.view_own',
            'expense.create',
        ]);
        $executive->syncPermissions([
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

        SystemSetting::updateOrCreate(['key' => 'ccc_financial'], [
            'value' => [
                'minimum_cash_reserve' => 1500,
                'weekly_debt_budget' => 5000,
                'monthly_debt_target' => 20000,
                'overdue_threshold' => 30,
                'critical_creditor_threshold' => 10000,
            ],
        ]);

        SystemSetting::updateOrCreate(['key' => 'ccc_smtp'], [
            'value' => [
                'smtp_host' => null,
                'smtp_port' => 587,
                'smtp_username' => null,
                'smtp_password' => null,
                'smtp_encryption' => 'tls',
                'sender_name' => 'PMMY Group',
                'sender_email' => 'hq@physiomobile.com',
            ],
        ]);

        foreach ([
            'inflow' => ['Patient Collection', 'Package Sales', 'Panel Collection', 'Corporate Collection', 'Insurance Collection', 'Refund Received', 'Director Injection', 'Other Income'],
            'outflow' => ['Salary', 'EPF', 'SOCSO', 'Rent', 'Utilities', 'Internet', 'Marketing', 'Software Subscription', 'Supplier Payment', 'Creditor Payment', 'Director Withdrawal', 'Other Expense'],
        ] as $type => $categories) {
            foreach ($categories as $category) {
                TransactionCategory::updateOrCreate(['name' => $category, 'type' => $type], ['status' => 'active']);
            }
        }

        BankAccount::firstOrCreate([
            'bank_name' => 'Maybank',
            'account_name' => 'PMMY Group Operating Account',
        ], [
            'account_number' => null,
            'opening_balance' => 0,
            'status' => 'active',
        ]);
    }
}
