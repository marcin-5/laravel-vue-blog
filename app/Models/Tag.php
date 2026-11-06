<?php

namespace App\Models;

use App\Observers\SitemapObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'blog_id',
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        static::created(static fn($tag) => app(SitemapObserver::class)->regenerateSitemap($tag));
        static::updated(static fn($tag) => app(SitemapObserver::class)->regenerateSitemap($tag));
        static::deleted(static fn($tag) => app(SitemapObserver::class)->regenerateSitemap($tag));
    }

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class)->withTimestamps();
    }

    public function landingPage(): HasOne
    {
        return $this->hasOne(TagLandingPage::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function setSlugAttribute(?string $value): void
    {
        $source = $value ?: ($this->attributes['name'] ?? null);
        $this->attributes['slug'] = $source ? Str::slug($source) : null;
    }
}
