<?php

namespace App\Models;

use Database\Factories\RecommendedLinkCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecommendedLinkCategory extends Model
{
    /** @use HasFactory<RecommendedLinkCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'blog_id',
        'name',
        'display_order',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    public function recommendedLinks(): HasMany
    {
        return $this->hasMany(RecommendedLink::class, 'category_id')->orderBy('display_order')->orderBy('id');
    }
}
