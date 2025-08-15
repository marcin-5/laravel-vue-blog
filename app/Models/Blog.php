<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
    ];

    /**
     * The owner of the blog (User).
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Ensure a slug is present. If not provided, generate from name.
     */
    public function setSlugAttribute(?string $value): void
    {
        $slug = $value ?: ($this->attributes['name'] ?? null);
        if ($slug) {
            $this->attributes['slug'] = Str::slug($slug);
        }
    }
}
