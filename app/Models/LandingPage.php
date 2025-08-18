<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPage extends Model
{
    use HasFactory;

    public const SIDEBAR_LEFT = 'left';
    public const SIDEBAR_RIGHT = 'right';
    public const SIDEBAR_NONE = 'none';

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
     * Accessor to get Markdown content rendered as HTML.
     */
    public function getContentHtmlAttribute(): string
    {
        $content = (string) ($this->content ?? '');
        if ($content === '') {
            return '';
        }
        $parser = new \ParsedownExtra();
        if (method_exists($parser, 'setSafeMode')) {
            $parser->setSafeMode(true);
        }
        return $parser->text($content);
    }

    /**
     * Ensure sidebar_position is only left, right, or none; default to right.
     */
    public function setSidebarPositionAttribute(?string $value): void
    {
        $value = $value ? strtolower($value) : null;
        $this->attributes['sidebar_position'] = in_array($value, [self::SIDEBAR_LEFT, self::SIDEBAR_RIGHT, self::SIDEBAR_NONE], true)
            ? $value
            : self::SIDEBAR_RIGHT;
    }
}
