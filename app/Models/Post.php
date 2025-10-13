<?php

namespace App\Models;

use App\Observers\SitemapObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use ParsedownExtra;

class Post extends Model
{
    use HasFactory;

    public const VIS_PUBLIC = 'public';
    public const VIS_REGISTERED = 'registered';
    protected $fillable = [
        'blog_id',
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

    /**
     * Accessor to get Markdown content rendered as HTML.
     */
    public function getContentHtmlAttribute(): string
    {
        $content = (string)($this->content ?? '');
        if ($content === '') {
            return '';
        }
        $parser = new ParsedownExtra();
        if (method_exists($parser, 'setSafeMode')) {
            $parser->setSafeMode(true);
        }
        return $parser->text($content);
    }

    /**
     * Ensure slug is set; if missing, generate from title.
     */
    public function setSlugAttribute(?string $value): void
    {
        $slug = $value ?: ($this->attributes['title'] ?? null);
        if ($slug) {
            $this->attributes['slug'] = Str::slug($slug);
        }
    }

    /**
     * Scope: only published posts (is_published = true and published_at <= now or null).
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Scope: visible to the public (non-registered).
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', self::VIS_PUBLIC);
    }

    /**
     * Scope: visible only to registered users.
     */
    public function scopeRegistered(Builder $query): Builder
    {
        return $query->where('visibility', self::VIS_REGISTERED);
    }

    /**
     * Scope: posts belonging to blogs owned by a specific user.
     */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->whereHas('blog', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * Scope: posts for a specific blog with ownership validation.
     */
    public function scopeForBlog(Builder $query, int $blogId, int $userId): Builder
    {
        $query->where('blog_id', $blogId);

        if ($userId) {
            $query->whereHas('blog', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        return $query;
    }
}
