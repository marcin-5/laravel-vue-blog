<?php

namespace App\Models;

use App\Models\Concerns\HasMarkdownContent;
use Database\Factories\PostExtensionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostExtension extends Model
{
    /** @use HasFactory<PostExtensionFactory> */
    use HasFactory;
    use HasMarkdownContent;

    protected $fillable = [
        'post_id',
        'title',
        'content',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
