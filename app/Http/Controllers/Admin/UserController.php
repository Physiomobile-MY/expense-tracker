<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isDirector(), 403);

        return view('admin.users.index', [
            'users' => User::with('department')->orderBy('name')->paginate(20),
            'departments' => Department::where('status', 'active')->orderBy('name')->get(),
            'roles' => config('expenseflow.roles'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isDirector(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'role' => ['required', Rule::in(array_keys(config('expenseflow.roles')))],
            'status' => ['required', 'in:active,inactive'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $this->ensureConfiguredRole($validated['role']);

        $user = User::create($validated);
        $user->syncRoles([$validated['role']]);

        return back()->with('status', 'User created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isDirector(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'role' => ['required', Rule::in(array_keys(config('expenseflow.roles')))],
            'status' => ['required', 'in:active,inactive'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if ($validated['password'] ?? null) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $this->ensureConfiguredRole($validated['role']);

        $user->update($validated);
        $user->syncRoles([$validated['role']]);

        return back()->with('status', 'User updated.');
    }

    private function ensureConfiguredRole(string $roleName): void
    {
        $permissions = $this->permissionsForRole($roleName);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])
            ->syncPermissions($permissions);
    }

    private function permissionsForRole(string $roleName): array
    {
        return match ($roleName) {
            'director_super_admin' => [
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
            ],
            'admin_finance' => [
                'expense.view_all',
                'expense.review',
                'expense.approve',
                'expense.reject',
                'expense.mark_paid',
                'expense.export',
                'ai_logs.view',
            ],
            'executive', 'staff' => [
                'expense.view_own',
                'expense.create',
            ],
            default => [],
        };
    }
}
