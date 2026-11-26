<?php

namespace App\Models;

use Database\Factories\RecommendedLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendedLink extends Model
{
    /** @use HasFactory<RecommendedLinkFactory> */
    use HasFactory;

    protected $fillable = [
        'url',
        'name',
        'description',
    ];

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }
}
