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
        'is_published',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    protected $appends = [
        'creation_date',
    ];

    /**
     * Accessor: returns the date portion of created_at as YYYY-MM-DD.
     */
    public function getCreationDateAttribute(): ?string
    {
        return $this->created_at?->toDateString();
    }

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
