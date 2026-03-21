<?php

namespace App\Models;

use Database\Factories\ExternalLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalLink extends Model
{
    /** @use HasFactory<ExternalLinkFactory> */
    use HasFactory;

    protected $fillable = [
        'post_id',
        'title',
        'url',
        'description',
        'reason',
        'display_order',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
