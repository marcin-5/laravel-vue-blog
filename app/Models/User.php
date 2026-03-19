<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\GroupRole;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Traits\HasRoles;
use Throwable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

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
        'blog_quota',
        'locale',
    ];

    // Ensure default role/blog_quota on create and adjust blog_quota when role changes
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (empty($user->role)) {
                $user->role = UserRole::User->value;
            }
            if ($user->blog_quota === null) {
                $user->blog_quota = 0;
            }
        });

        static::created(function (User $user): void {
            if (self::shouldSyncPermissions()) {
                try {
                    $user->assignRole($user->role);
                } catch (Throwable) {
                }
            }
        });

        static::updating(function (User $user): void {
            if ($user->isDirty('role')) {
                // If switching to blogger, default to 1 (unless explicitly set in code)
                if ($user->role === UserRole::Blogger->value && $user->getOriginal(
                    'role',
                ) !== UserRole::Blogger->value) {
                    // Only set when not explicitly provided by code performing the update
                    if ($user->blog_quota === null) {
                        $user->blog_quota = 1;
                    }
                }

                // If switching to regular user, enforce 0
                if ($user->role === UserRole::User->value) {
                    $user->blog_quota = 0;
                }

                if (self::shouldSyncPermissions()) {
                    try {
                        $user->syncRoles([$user->role]);
                    } catch (Throwable) {
                    }
                }
            }
        });
    }

    /**
     * Check if permission tables are available before use.
     */
    public static function shouldSyncPermissions(?bool $reset = false): bool
    {
        static $canSync = null;

        if ($reset) {
            $canSync = null;
        }

        if (config('permission.disabled', false)) {
            return false;
        }

        if ($canSync !== null) {
            return $canSync;
        }

        try {
            $tableNames = config('permission.table_names', []);

            $canSync = Schema::hasTable($tableNames['roles'] ?? 'roles')
                && Schema::hasTable($tableNames['permissions'] ?? 'permissions')
                && Schema::hasTable($tableNames['model_has_roles'] ?? 'model_has_roles')
                && Schema::hasTable($tableNames['model_has_permissions'] ?? 'model_has_permissions')
                && Schema::hasTable($tableNames['role_has_permissions'] ?? 'role_has_permissions');
        } catch (Throwable) {
            $canSync = false;
        }

        return $canSync;
    }

    public function canCreateBlog(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        if (!$this->isBlogger()) {
            return false;
        }
        // Bloggers can create a new blog only if they are under the blog_quota limit
        $current = $this->blogs()->count();
        $limit = max(0, (int) ($this->blog_quota ?? 1));

        return $current < $limit;
    }

    public function isAdmin(): bool
    {
        if (!self::shouldSyncPermissions()) {
            return ($this->role ?? $this->getOriginal('role')) === UserRole::Admin->value;
        }

        try {
            return $this->hasRole(UserRole::Admin->value);
        } catch (Throwable) {
            return ($this->role ?? $this->getOriginal('role')) === UserRole::Admin->value;
        }
    }

    public function isBlogger(): bool
    {
        if (!self::shouldSyncPermissions()) {
            return ($this->role ?? $this->getOriginal('role')) === UserRole::Blogger->value;
        }

        try {
            return $this->hasRole(UserRole::Blogger->value);
        } catch (Throwable) {
            return ($this->role ?? $this->getOriginal('role')) === UserRole::Blogger->value;
        }
    }

    /**
     * A user can own many blogs.
     */
    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class, 'user_id');
    }

    /**
     * Check if user has specific ability globally or in group context.
     */
    public function hasAbility(string $ability, ?Group $group = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (self::shouldSyncPermissions()) {
            try {
                if ($this->hasPermissionTo($ability)) {
                    return true;
                }
            } catch (Throwable) {
                // Ignore if permission doesn't exist
            }
        }

        if ($group) {
            $membership = $this->groups()->where('groups.id', $group->id)->first()?->pivot;
            if ($membership) {
                $role = GroupRole::tryFrom($membership->role);

                return $role && in_array($ability, $role->abilities(), true);
            }
        }

        return false;
    }

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if (!self::shouldSyncPermissions()) {
            return false;
        }

        try {
            return parent::hasPermissionTo($permission, $guardName);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Grupy, do których użytkownik należy.
     */
    public function groups(): BelongsToMany
    {
        return $this
            ->belongsToMany(Group::class, 'group_user')
            ->using(GroupMember::class)
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    public function checkPermissionTo($permission, $guardName = null): bool
    {
        if (!self::shouldSyncPermissions()) {
            return false;
        }

        try {
            return parent::checkPermissionTo($permission, $guardName);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Grupy, których użytkownik jest właścicielem.
     */
    public function ownedGroups(): HasMany
    {
        return $this->hasMany(Group::class, 'user_id');
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
            'blog_quota' => 'integer',
        ];
    }
}
