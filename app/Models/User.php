<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_BLOGGER = 'blogger';
    public const ROLE_USER = 'user';

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
                $user->role = self::ROLE_USER;
            }
            if ($user->blog_quota === null) {
                $user->blog_quota = 0;
            }
        });

        static::updating(function (User $user): void {
            if ($user->isDirty('role')) {
                // If switching to blogger, default to 1 (unless explicitly set in code)
                if ($user->role === self::ROLE_BLOGGER && $user->getOriginal('role') !== self::ROLE_BLOGGER) {
                    // Only set when not explicitly provided by code performing the update
                    if ($user->blog_quota === null || $user->blog_quota === $user->getOriginal('blog_quota')) {
                        $user->blog_quota = 1;
                    }
                }

                // If switching to regular user, enforce 0
                if ($user->role === self::ROLE_USER) {
                    $user->blog_quota = 0;
                }
            }
        });
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
        $limit = max(0, (int)($this->blog_quota ?? 1));
        return $current < $limit;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isBlogger(): bool
    {
        return $this->role === self::ROLE_BLOGGER;
    }

    /**
     * A user can own many blogs.
     */
    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class, 'user_id');
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
