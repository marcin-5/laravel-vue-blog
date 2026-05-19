<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

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
