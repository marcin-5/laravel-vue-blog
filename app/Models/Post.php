<?php

namespace App\Models;

use App\Observers\SitemapObserver;
use App\Viewable;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use ParsedownExtra;

class Post extends Model
{
    use HasFactory;
    use Viewable;

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

    public function newsletterLogs(): HasMany
    {
        return $this->hasMany(NewsletterLog::class);
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
        $parser = new ParsedownExtra;
        if (method_exists($parser, 'setSafeMode')) {
            $parser->setSafeMode(false);
        }
        $html = $parser->text($content);
        $purifier = new HTMLPurifier(HTMLPurifier_Config::createDefault());

        return $purifier->purify($html);
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
            ->public()
            ->orderByPublicationDate();
    }

    /**
     * Scope for public listing views (includes common select fields)
     */
    public function scopeForPublicListing(Builder $query): Builder
    {
        return $query->forPublicView()
            ->select(['id', 'blog_id', 'title', 'slug', 'excerpt', 'published_at', 'created_at']);
    }

    /**
     * Scope to find post by slug within published public posts
     */
    public function scopeFindBySlugForPublic(Builder $query, string $slug): Builder
    {
        return $query->forPublicView()
            ->where('slug', $slug);
    }
}
