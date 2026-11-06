<?php

namespace App\Models;

use App\Models\Concerns\HasMarkdownContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TagLandingPage extends Model
{
    /** @use HasFactory<\Database\Factories\TagLandingPageFactory> */
    use HasFactory;
    use HasMarkdownContent;

    protected $fillable = [
        'tag_id',
        'content',
    ];

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
