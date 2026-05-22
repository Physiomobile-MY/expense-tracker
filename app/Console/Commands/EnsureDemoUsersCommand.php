<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EnsureDemoUsersCommand extends Command
{
    protected $signature = 'expenseflow:ensure-demo-users {--password=password}';

    protected $description = 'Create or reset the default Physiomobile ExpenseFlow users.';

    public function handle(): int
    {
        $password = (string) $this->option('password');

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

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

        $roles = ['director_super_admin', 'admin_finance', 'executive', 'staff'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        Role::where('name', 'director_super_admin')->first()?->syncPermissions($permissions);
        Role::where('name', 'admin_finance')->first()?->syncPermissions([
            'expense.view_all',
            'expense.review',
            'expense.approve',
            'expense.reject',
            'expense.mark_paid',
            'expense.export',
            'ai_logs.view',
        ]);
        Role::where('name', 'executive')->first()?->syncPermissions([
            'expense.view_own',
            'expense.create',
        ]);
        Role::where('name', 'staff')->first()?->syncPermissions([
            'expense.view_own',
            'expense.create',
        ]);

        $departments = [
            'MGT' => 'Management',
            'OPS' => 'Operations',
            'CLI' => 'Clinical',
        ];

        foreach ($departments as $code => $name) {
            Department::firstOrCreate(['code' => $code], [
                'name' => $name,
                'status' => 'active',
            ]);
        }

        $users = [
            [
                'name' => 'Nidzam Yatimi',
                'email' => 'nidzamyatimi@physiomobile.com',
                'role' => 'director_super_admin',
                'department_code' => 'MGT',
            ],
            [
                'name' => 'Saiful',
                'email' => 'saiful@physiomobile.com',
                'role' => 'director_super_admin',
                'department_code' => 'MGT',
            ],
            [
                'name' => 'Executive Staff 1',
                'email' => 'executive1@physiomobile.com',
                'role' => 'executive',
                'department_code' => 'OPS',
            ],
            [
                'name' => 'Executive Staff 2',
                'email' => 'executive2@physiomobile.com',
                'role' => 'executive',
                'department_code' => 'CLI',
            ],
        ];

        foreach ($users as $userData) {
            $department = Department::where('code', $userData['department_code'])->first();

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'department_id' => $department?->id,
                    'role' => $userData['role'],
                    'status' => 'active',
                    'must_change_password' => true,
                    'password' => Hash::make($password),
                ]
            );

            $user->syncRoles([$userData['role']]);
            $this->line($userData['email'].' reset.');
        }

        User::whereIn('email', [
            'director@physiomobile.com',
            'finance@physiomobile.com',
            'staff@physiomobile.com',
        ])->update(['status' => 'inactive']);

        $this->info('Default users are ready. Temporary password: '.$password);
        $this->info('Users must change password after first login.');

        return self::SUCCESS;
    }
}
