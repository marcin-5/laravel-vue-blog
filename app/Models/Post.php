<?php

namespace App\Models;

use App\Builders\PostBuilder;
use App\Models\Concerns\HasMarkdownContent;
use App\Observers\SitemapObserver;
use App\Viewable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[UseEloquentBuilder(PostBuilder::class)]
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
        'seo_title',
        'slug',
        'excerpt',
        'summary',
        'content',
        'is_published',
        'visibility',
        'published_at',
    ];

    protected $appends = [];

    protected static function booted(): void
    {
        static::saving(function (Post $post) {
            // Ensure slug is generated from title if changed or missing
            if ($post->title && (!$post->slug || ($post->isDirty('title') && !$post->isDirty('slug')))) {
                $post->slug = $post->title;
            }

            // Set published_at when post is first published
            if ($post->is_published && !$post->getOriginal('is_published') && !$post->published_at) {
                $post->published_at = now();
            }
        });

        static::created(fn($post) => app(SitemapObserver::class)->regenerateSitemap($post));
        static::updated(fn($post) => app(SitemapObserver::class)->regenerateSitemap($post));
        static::deleted(fn($post) => app(SitemapObserver::class)->regenerateSitemap($post));
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

    public function relatedPosts(): HasMany
    {
        return $this->hasMany(RelatedPost::class)->orderBy('display_order');
    }

    public function externalLinks(): HasMany
    {
        return $this->hasMany(ExternalLink::class)->orderBy('display_order');
    }

    /**
     * Post extensions assigned to this post (via pivot)
     */
    public function extensions(): BelongsToMany
    {
        return $this
            ->belongsToMany(
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
        return $this
            ->belongsToMany(
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
     * Tags assigned to this post.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * Accessor to get Markdown summary rendered as HTML.
     */
    public function getSummaryHtmlAttribute(): string
    {
        return $this->renderMarkdown((string) ($this->summary ?? ''));
    }

    /**
     * Get attached_at attribute from pivot
     */
    public function getAttachedAtAttribute(): ?Carbon
    {
        return $this->attachment?->created_at;
    }

    /**
     * Get the SEO title, falling back to title if not specifically requested.
     */
    public function getSeoTitleAttribute(?string $value): ?string
    {
        return $value;
    }

    /**
     * Get the SEO title with fallback to title.
     */
    public function getSeoTitleWithFallback(): string
    {
        return $this->seo_title ?: $this->title;
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the public URL for the post.
     */
    public function getPublicUrlAttribute(): string
    {
        if ($this->group_id && $this->group) {
            try {
                return route('group.post', ['group' => $this->group->slug, 'postSlug' => $this->slug]);
            } catch (\Throwable) {
                return '';
            }
        }

        if (!$this->blog) {
            return '';
        }

        try {
            return route('blog.public.post', [
                'blog' => $this->blog->slug,
                'postSlug' => $this->slug,
                'mainDomain' => $this->blog->main_domain,
            ]);
        } catch (\Throwable) {
            return '';
        }
    }
}
