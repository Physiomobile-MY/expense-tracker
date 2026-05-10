<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EnsureDemoUsersCommand extends Command
{
    protected $signature = 'expenseflow:ensure-demo-users {--password=password}';

    protected $description = 'Create or reset the default Physiomobile ExpenseFlow demo users.';

    public function handle(): int
    {
        $password = (string) $this->option('password');

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $roles = ['director_super_admin', 'admin_finance', 'staff'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $departments = [
            'MGT' => 'Management',
            'FIN' => 'Finance',
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
                'name' => 'Director Super Admin',
                'email' => 'director@physiomobile.com',
                'role' => 'director_super_admin',
                'department_code' => 'MGT',
            ],
            [
                'name' => 'Finance Admin',
                'email' => 'finance@physiomobile.com',
                'role' => 'admin_finance',
                'department_code' => 'FIN',
            ],
            [
                'name' => 'Staff Member',
                'email' => 'staff@physiomobile.com',
                'role' => 'staff',
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
                    'password' => Hash::make($password),
                ]
            );

            $user->syncRoles([$userData['role']]);
            $this->line($userData['email'].' reset.');
        }

        $this->info('Demo users are ready. Password: '.$password);

        return self::SUCCESS;
    }
}
