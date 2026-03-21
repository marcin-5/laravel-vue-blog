<?php

namespace App\Models;

use Database\Factories\RelatedPostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelatedPost extends Model
{
    /** @use HasFactory<RelatedPostFactory> */
    use HasFactory;

    protected $fillable = [
        'post_id',
        'blog_id',
        'related_post_id',
        'reason',
        'display_order',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    public function relatedPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'related_post_id');
    }
}
