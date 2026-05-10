<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'department_id',
        'manager_id',
        'role',
        'status',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function manager()
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function expenseRecords()
    {
        return $this->hasMany(ExpenseRecord::class);
    }

    public function notifications()
    {
        return $this->hasMany(ExpenseNotification::class);
    }

    public function isDirector(): bool
    {
        return $this->role === 'director_super_admin' || $this->hasRole('director_super_admin');
    }

    public function isFinance(): bool
    {
        return $this->role === 'admin_finance' || $this->hasRole('admin_finance');
    }

    public function isStaffLevel(): bool
    {
        return $this->role === 'staff' || $this->hasRole('staff');
    }

    public function canManageExpenses(): bool
    {
        return $this->isDirector() || $this->isFinance();
    }

    public function roleLabel(): string
    {
        return config('expenseflow.roles')[$this->role] ?? str($this->role)->headline()->toString();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
