<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EnsureDemoUsersCommand extends Command
{
    protected $signature = 'expenseflow:ensure-demo-users {--password=} {--generate : Generate one-time passwords for each demo user} {--force : Confirm this local/testing bootstrap action} {--reactivate : Reactivate existing inactive demo users}';

    protected $description = 'Create local/testing ExpenseFlow demo director users with deployment-specific credentials.';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('Refusing to create or reset privileged demo users without --force.');

            return self::FAILURE;
        }

        if (app()->environment('production')) {
            $this->error('Refusing to run demo user bootstrap in production. Use a deployment-specific admin invitation/reset process instead.');

            return self::FAILURE;
        }

        $sharedPassword = $this->option('password');

        if ($sharedPassword && ! $this->option('generate')) {
            $this->warn('A shared credential was provided. Prefer --generate for one-time per-user credentials.');

            if (strlen((string) $sharedPassword) < 14) {
                $this->error('Bootstrap credentials must be at least 14 characters.');

                return self::FAILURE;
            }
        }

        if (! $sharedPassword && ! $this->option('generate')) {
            $this->error('Provide --generate or a deployment-specific --password value.');

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
        ];

        foreach ($departments as $code => $name) {
            Department::firstOrCreate(['code' => $code], [
                'name' => $name,
                'status' => 'active',
            ]);
        }

        $users = [
            [
                'name' => 'Demo Director One',
                'email' => 'director.one@example.test',
                'role' => 'director_super_admin',
                'department_code' => 'MGT',
            ],
            [
                'name' => 'Demo Director Two',
                'email' => 'director.two@example.test',
                'role' => 'director_super_admin',
                'department_code' => 'MGT',
            ],
        ];

        foreach ($users as $userData) {
            $department = Department::where('code', $userData['department_code'])->first();

            $password = $this->option('generate') ? Str::password(24) : (string) $sharedPassword;
            $existing = User::where('email', $userData['email'])->first();

            if ($existing?->status === 'inactive' && ! $this->option('reactivate')) {
                $this->warn($userData['email'].' exists but is inactive; skipping without --reactivate.');

                continue;
            }

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'department_id' => $department?->id,
                    'role' => $userData['role'],
                    'status' => 'active',
                    'must_change_password' => true,
                    'password' => Hash::make($password),
                    'remember_token' => null,
                ]
            );

            $user->syncRoles([$userData['role']]);
            $this->line($userData['email'].' ready. One-time password: '.$password);
        }

        User::whereIn('email', [
            'director@physiomobile.com',
            'finance@physiomobile.com',
            'staff@physiomobile.com',
        ])->update(['status' => 'inactive']);

        $this->info('Demo director users are ready. Store generated one-time passwords securely and rotate immediately after first login.');

        return self::SUCCESS;
    }
}
