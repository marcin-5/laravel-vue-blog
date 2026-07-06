<?php

namespace App\Models;

use App\Observers\SitemapObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::created(fn($tag) => app(SitemapObserver::class)->regenerateSitemap($tag));
        static::updated(fn($tag) => app(SitemapObserver::class)->regenerateSitemap($tag));
        static::deleted(fn($tag) => app(SitemapObserver::class)->regenerateSitemap($tag));
    }

    protected $fillable = [
        'blog_id',
        'name',
        'slug',
    ];

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class)->withTimestamps();
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
