<?php

namespace App\Models;

use App\Models\Concerns\HasMarkdownContent;
use App\Observers\SitemapObserver;
use App\Viewable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;
    use HasMarkdownContent;
    use Viewable;

    public const string VIS_PUBLIC = 'public';

    public const string VIS_REGISTERED = 'registered';

    public const string VIS_UNLISTED = 'unlisted';

    public const string VIS_EXTENSION = 'extension';

    public const string VIS_RESTRICTED = 'restricted';

    protected $fillable = [
        'blog_id',
        'group_id',
        'user_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'is_published',
        'visibility',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(fn() => app(SitemapObserver::class)->regenerateSitemap());
        static::updated(fn() => app(SitemapObserver::class)->regenerateSitemap());
        static::deleted(fn() => app(SitemapObserver::class)->regenerateSitemap());
    }

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function newsletterLogs(): HasMany
    {
        return $this->hasMany(NewsletterLog::class);
    }

    /**
     * Post extensions assigned to this post (via pivot)
     */
    public function extensions(): BelongsToMany
    {
        return $this->belongsToMany(
            Post::class,
            'post_extensions',
            'post_id',
            'extension_post_id',
        )
            ->withPivot(['display_order', 'created_at'])
            ->as('attachment')
            ->orderByPivot('display_order')
            ->withTimestamps();
    }

    /**
     * Main posts to which this post is assigned as an extension
     */
    public function parentPosts(): BelongsToMany
    {
        return $this->belongsToMany(
            Post::class,
            'post_extensions',
            'extension_post_id',
            'post_id',
        )
            ->withPivot(['display_order', 'created_at'])
            ->as('attachment')
            ->withTimestamps();
    }

    /**
     * Get attached_at attribute from pivot
     */
    public function getAttachedAtAttribute(): ?Carbon
    {
        return $this->attachment?->created_at;
    }

    /**
     * Ensure slug is set; if missing, generate from title.
     */
    public function setSlugAttribute(?string $value): void
    {
        $source = $value ?: ($this->attributes['title'] ?? null);

        if ($source === null) {
            $this->attributes['slug'] = null;

            return;
        }

        $this->attributes['slug'] = Str::slug($source);
    }

    /**
     * Scope for extension posts
     */
    public function scopeExtensionType(Builder $query): Builder
    {
        return $query->where('visibility', self::VIS_EXTENSION);
    }

    /**
     * Scope for regular (non-extension) posts
     */
    public function scopeRegularPosts(Builder $query): Builder
    {
        return $query->where('visibility', '!=', self::VIS_EXTENSION);
    }

    /**
     * Scope to only published posts (published_at is set and not in the future)
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope to only public posts (visibility = 'public')
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', self::VIS_PUBLIC);
    }

    /**
     * Scope to order by publication date (published_at, then created_at)
     */
    public function scopeOrderByPublicationDate(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('published_at', $direction)
            ->orderBy('created_at', $direction);
    }

    /**
     * Composite scope: published + public + ordered
     */
    public function scopeForPublicView(Builder $query): Builder
    {
        return $query->published()
            ->whereIn('visibility', [self::VIS_PUBLIC, self::VIS_UNLISTED])
            ->orderByPublicationDate();
    }

    /**
     * Scope for posts and extensions relevant for newsletter
     */
    public function scopeForNewsletter(Builder $query, DateTimeInterface $since): Builder
    {
        return $query->published()
            ->where(function (Builder $q) use ($since) {
                // Regular posts published since $since
                $q->where(function (Builder $q2) use ($since) {
                    $q2->whereIn('visibility', [self::VIS_PUBLIC, self::VIS_UNLISTED])
                        ->where('published_at', '>=', $since);
                })
                    // OR Extensions attached to public posts since $since
                    ->orWhere(function (Builder $q2) use ($since) {
                        $q2->extensionType()
                            ->whereHas('parentPosts', function (Builder $q3) use ($since) {
                                $q3->whereIn('visibility', [self::VIS_PUBLIC, self::VIS_UNLISTED])
                                    ->where('post_extensions.created_at', '>=', $since);
                            });
                    });
            });
    }

    /**
     * Scope for public listing views (includes common select fields)
     */
    public function scopeForPublicListing(Builder $query): Builder
    {
        return $query->published()
            ->public()
            ->regularPosts()
            ->orderByPublicationDate()
            ->select(['id', 'blog_id', 'title', 'slug', 'excerpt', 'published_at', 'created_at', 'visibility']);
    }

    /**
     * Scope to find post by slug within published public posts
     */
    public function scopeFindBySlugForPublic(Builder $query, string $slug): Builder
    {
        return $query->forPublicView()
            ->where('slug', $slug);
    }

    /**
     * Scope for group posts, visible to logged-in members
     */
    public function scopeForGroupView(Builder $query): Builder
    {
        return $query->published()
            ->regularPosts()
            ->orderByPublicationDate();
    }
}
