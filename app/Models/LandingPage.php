<?php

namespace App\Models;

use App\Models\Concerns\HasMarkdownContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPage extends Model
{
    use HasFactory;
    use HasMarkdownContent;

    public const string SIDEBAR_LEFT = 'left';
    public const string SIDEBAR_RIGHT = 'right';
    public const string SIDEBAR_NONE = 'none';

    protected $fillable = [
        'blog_id',
        'content',
        'sidebar_position',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    /**
     * Ensure sidebar_position is only left, right, or none; default to right.
     */
    public function setSidebarPositionAttribute(?string $value): void
    {
        $value = $value ? strtolower($value) : null;
        $this->attributes['sidebar_position'] = in_array(
            $value,
            [self::SIDEBAR_LEFT, self::SIDEBAR_RIGHT, self::SIDEBAR_NONE],
            true,
        )
            ? $value
            : self::SIDEBAR_RIGHT;
    }
}
