<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_SALES = 'sales';

    public const ROLE_WAREHOUSE = 'warehouse';

    public const ROLES = [
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_MANAGER => 'Manager',
        self::ROLE_SALES => 'Sales Staff',
        self::ROLE_WAREHOUSE => 'Warehouse Staff',
    ];

    /**
     * Which "areas" of the app each role is allowed into.
     */
    private const AREA_ACCESS = [
        self::ROLE_ADMIN => ['products', 'inventory', 'customers', 'orders', 'purchases', 'finance', 'reports', 'users'],
        self::ROLE_MANAGER => ['products', 'inventory', 'customers', 'orders', 'purchases', 'finance', 'reports'],
        self::ROLE_SALES => ['customers', 'orders'],
        self::ROLE_WAREHOUSE => ['inventory'],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }

    public function canAccess(string $area): bool
    {
        return in_array($area, self::AREA_ACCESS[$this->role] ?? [], true);
    }

    /**
     * True if this user is an admin and no other admin account exists.
     * Deleting or demoting them would lock everyone out of user management.
     */
    public function isLastRemainingAdmin(): bool
    {
        return $this->isAdmin() && static::where('role', self::ROLE_ADMIN)->count() <= 1;
    }
}
