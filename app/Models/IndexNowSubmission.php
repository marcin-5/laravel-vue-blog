<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndexNowSubmission extends Model
{
    protected $fillable = [
        'url',
        'blog_id',
        'post_id',
        'succeeded',
        'http_status',
        'error_message',
        'submitted_at',
    ];

    protected $casts = [
        'succeeded' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
