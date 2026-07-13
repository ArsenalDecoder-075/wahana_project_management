<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'branch_id',
        'name',
        'email',
        'password',
        'type',
        'remember_token',
    ];

    public function managerUser()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function employeeUser()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * User type constants.
     */
    const TYPE_USER = 0;
    const TYPE_ADMIN = 1;
    const TYPE_MANAGER = 2;

    /**
     * Get the branch that this user belongs to.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Check if user is an admin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->type === self::TYPE_ADMIN;
    }

    /**
     * Check if user is a manager.
     *
     * @return bool
     */
    public function isManager()
    {
        return $this->type === self::TYPE_MANAGER;
    }

    /**
     * Check if user is a regular user.
     *
     * @return bool
     */
    public function isRegularUser()
    {
        return $this->type === self::TYPE_USER;
    }
}
