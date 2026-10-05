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

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role_id',
        'pasar_id',
        'is_active',
        'last_login_at',
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
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function pasar()
    {
        return $this->belongsTo(Pasar::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role?->name, ['super_admin', 'admin']);
    }

    public function canValidate(): bool
    {
        return in_array($this->role?->name, ['super_admin', 'admin', 'validator']);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role?->name === 'super_admin') {
            return true;
        }

        return $this->role?->permissions()->where('name', $permission)->where('is_active', true)->exists() ?? false;
    }
}
