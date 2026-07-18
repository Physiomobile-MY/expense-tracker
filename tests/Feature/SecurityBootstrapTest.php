<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_sql_dump_with_user_inserts_is_not_tracked(): void
    {
        $dumpPath = base_path('database/sql/physiomobile_expenseflow_mysql.sql');

        $this->assertFileDoesNotExist($dumpPath);
    }

    public function test_demo_user_command_requires_explicit_force_and_credential_strategy(): void
    {
        $this->artisan('expenseflow:ensure-demo-users')
            ->expectsOutput('Refusing to create or reset privileged demo users without --force.')
            ->assertFailed();
    }

    public function test_demo_user_command_generates_synthetic_directors_for_local_bootstrap(): void
    {
        $this->artisan('expenseflow:ensure-demo-users --generate --force')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'director.one@example.test',
            'role' => 'director_super_admin',
            'status' => 'active',
            'must_change_password' => true,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'director.two@example.test',
            'role' => 'director_super_admin',
            'status' => 'active',
            'must_change_password' => true,
        ]);

        $this->assertSame(2, User::query()->where('role', 'director_super_admin')->count());
    }
}
