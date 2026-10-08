<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    /**
     * Get the roles assigned to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'user_roles',
            'user_id',
            'role_id'
        );
    }

    /**
     * Get the direct permissions assigned to this user.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'user_permissions',
            'user_id',
            'permission_id'
        );
    }

    /**
     * Get the denied permissions for this user.
     */
    public function deniedPermissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'user_denied_permissions',
            'user_id',
            'permission_id'
        );
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->where('name', $role)
            ->exists();
    }

    /**
     * Check if the user has a specific permission.
     * 
     * Effective permissions = (role permissions + direct permissions) - denied permissions
     */
    public function hasPermission(string $permission): bool
    {
        // Denied permissions take precedence
        if ($this->deniedPermissions()->where('name', $permission)->exists()) {
            return false;
        }

        // Check role permissions
        if ($this->roles()->whereHas('permissions', fn ($q) => $q->where('name', $permission))->exists()) {
            return true;
        }

        // Check direct permissions
        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     * Get all effective permissions for this user.
     * 
     * @return Collection<int, Permission>
     */
    public function allPermissions(): Collection
    {
        $rolePermissions = $this->roles->flatMap->permissions;
        $directPermissions = $this->permissions;
        $deniedPermissions = $this->deniedPermissions;

        return $rolePermissions
            ->merge($directPermissions)
            ->unique('id')
            ->reject(fn ($p) => $deniedPermissions->contains('id', $p->id));
    }

    /**
     * Get the teacher associated with this user.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}