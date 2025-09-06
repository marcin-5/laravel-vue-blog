<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_published
 * @property string $locale
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read User $user
 * @property-read LandingPage|null $landingPage
 * @property-read Collection<int, Post> $posts
 * @property-read Collection<int, Category> $categories
 * @property-read string|null $creation_date
 */
class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'is_published',
        'locale',
    ];
    protected $casts = [
        'is_published' => 'boolean',
    ];
    protected $appends = [
        'creation_date',
    ];

    /**
     * The user who owns the blog.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * One landing page per blog.
     */
    public function landingPage(): HasOne
    {
        return $this->hasOne(LandingPage::class);
    }

    /**
     * Blog posts.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Categories assigned to this blog.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'blog_category');
    }

    /**
     * Accessor: returns the date portion of created_at as YYYY-MM-DD.
     */
    public function getCreationDateAttribute(): ?string
    {
        return $this->created_at?->toDateString();
    }
}
