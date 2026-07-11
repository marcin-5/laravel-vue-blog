<?php

namespace App\Models;

use App\Builders\BlogBuilder;
use App\Observers\SitemapObserver;
use App\Viewable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Builder;
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
 * @property string|null $motto
 * @property string|null $footer
 * @property bool $is_published
 * @property string $locale
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read LandingPage|null $landingPage
 * @property-read Collection<int, Post> $posts
 * @property-read Collection<int, Category> $categories
 * @property-read string|null $creation_date
 */
#[UseEloquentBuilder(BlogBuilder::class)]
class Blog extends Model
{
    use HasFactory;
    use Viewable;

    protected $fillable = [
        'user_id',
        'name',
        'seo_title',
        'slug',
        'description',
        'motto',
        'footer',
        'is_published',
        'locale',
        'sidebar',
        'page_size',
        'theme',
        'about',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sidebar' => 'integer',
        'page_size' => 'integer',
        'theme' => 'array',
    ];

    protected $appends = [
        'creation_date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('locale', function (Builder $builder) {
            // Do not apply the locale scope in console
            if (app()->runningInConsole()) {
                return;
            }

            // Skip locale scoping for admin/API areas where locale is managed differently
            if (request()->is(
                'dashboard*',
                'settings*',
                'admin*',
                '_/*',
                'api/admin/*',
            )) {
                return;
            }

            // On blog subdomains (e.g. slug.osobliwy.localhost), we must NOT scope by app locale
            // because route-model binding needs to find the blog regardless of current locale.
            // We still want locale scoping on main domains (welcome page etc.).
            if (self::fromHost((string) request()->getHost())) {
                return;
            }

            $builder->where('blogs.locale', app()->getLocale());
        });

        static::created(fn($blog) => app(SitemapObserver::class)->regenerateSitemap($blog));
        static::updated(fn($blog) => app(SitemapObserver::class)->regenerateSitemap($blog));
        static::deleted(fn($blog) => app(SitemapObserver::class)->regenerateSitemap($blog));
    }

    /**
     * Resolve a blog instance from the given hostname.
     */
    public static function fromHost(string $host): ?self
    {
        $mainDomains = array_filter([
            config('app.domain'),
            config('app.domain_secondary'),
        ]);

        foreach ($mainDomains as $domain) {
            if ($domain && str_ends_with($host, '.' . $domain)) {
                $slug = str_replace('.' . $domain, '', $host);

                return self::withoutGlobalScopes()->where('slug', $slug)->first();
            }
        }

        return null;
    }

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
     * Tags defined for this blog.
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * Newsletter subscriptions for this blog.
     */
    public function newsletterSubscriptions(): HasMany
    {
        return $this->hasMany(NewsletterSubscription::class);
    }

    /**
     * Accessor: returns the date portion of created_at as YYYY-MM-DD.
     */
    public function getCreationDateAttribute(): ?string
    {
        return $this->created_at?->toDateString();
    }

    /**
     * Get the SEO title, falling back to name if not specifically requested.
     */
    public function getSeoTitleAttribute(?string $value): ?string
    {
        return $value;
    }

    /**
     * Get the SEO title with fallback to name.
     */
    public function getSeoTitleWithFallback(): string
    {
        return $this->seo_title ?: $this->name;
    }

    /**
     * Determine sidebar placement from landing page settings.
     */
    public function getSidebarPositionAttribute(): string
    {
        $sidebar = (int) ($this->sidebar ?? 0);

        if ($sidebar === 0) {
            return LandingPage::SIDEBAR_NONE;
        }

        return $sidebar < 0 ? LandingPage::SIDEBAR_LEFT : LandingPage::SIDEBAR_RIGHT;
    }

    /**
     * Get the main domain for the blog based on its locale.
     */
    public function getMainDomainAttribute(): string
    {
        return $this->locale === 'pl' ? config('app.domain') : config('app.domain_secondary');
    }

    /**
     * Get the public URL for the blog landing page.
     */
    public function getPublicUrlAttribute(): string
    {
        if (empty($this->slug)) {
            return '';
        }

        try {
            return route('blog.public.landing', ['blog' => $this->slug, 'mainDomain' => $this->main_domain]);
        } catch (\Throwable) {
            return '';
        }
    }
}
