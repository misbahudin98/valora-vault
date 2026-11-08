<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'token_version'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['status'];

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
            'token_version' => 'integer',
        ];
    }

    /**
     * Parse raw role value into clean array of roles.
     */
    public static function parseRoles(mixed $value): array
    {
        if (is_null($value) || $value === '') {
            return ['admin'];
        }
        if (is_array($value)) {
            $cleaned = array_values(array_unique(array_filter(array_map('trim', $value))));
            return empty($cleaned) ? ['admin'] : $cleaned;
        }
        $decoded = json_decode((string) $value, true);
        if (is_array($decoded)) {
            $cleaned = array_values(array_unique(array_filter(array_map('trim', $decoded))));
            return empty($cleaned) ? ['admin'] : $cleaned;
        }
        $cleaned = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) $value)))));
        return empty($cleaned) ? ['admin'] : $cleaned;
    }

    /**
     * Handle multiple roles storage in a single record without consolidation or alias mutators.
     * Accepts array (e.g. ['admin', 'manager']) or string ('admin') and preserves exact assigned roles.
     */
    protected function role(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return self::parseRoles($value);
            },
            set: function ($value) {
                $cleaned = self::parseRoles($value);
                return json_encode($cleaned);
            }
        );
    }

    /**
     * Backward-compatible status accessor: dynamically derived from is_active.
     */
    public function getStatusAttribute(): string
    {
        return $this->is_active ? 'active' : 'suspended';
    }

    /**
     * Helper to verify active account state.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function hasRole(string ...$roles): bool
    {
        $currentRoles = self::parseRoles($this->role);
        return !empty(array_intersect($roles, $currentRoles));
    }

    public function hasAllRoles(string ...$roles): bool
    {
        $currentRoles = self::parseRoles($this->role);
        return empty(array_diff($roles, $currentRoles));
    }

    /**
     * Get aggregated unique permissions based on the user's assigned roles directly.
     * Admin role automatically receives wildcard permission ['*'].
     */
    public function getPermissions(): array
    {
        if ($this->isAdmin()) {
            return ['*'];
        }

        $roleMap = config('permissions.roles', []);
        $perms = [];
        $currentRoles = self::parseRoles($this->role);

        foreach ($currentRoles as $r) {
            if (isset($roleMap[$r]) && is_array($roleMap[$r])) {
                $perms = array_merge($perms, $roleMap[$r]);
            }
        }

        return array_values(array_unique($perms));
    }

    /**
     * Check if user holds any of the given permissions (or is admin).
     */
    public function hasPermission(string ...$permissions): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $userPerms = $this->getPermissions();
        if (in_array('*', $userPerms, true)) {
            return true;
        }

        return !empty(array_intersect($permissions, $userPerms));
    }

    /**
     * Check if user holds all of the given permissions (or is admin).
     */
    public function hasAllPermissions(string ...$permissions): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $userPerms = $this->getPermissions();
        if (in_array('*', $userPerms, true)) {
            return true;
        }

        return empty(array_diff($permissions, $userPerms));
    }

    public function devices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserDevice::class);
    }
}
