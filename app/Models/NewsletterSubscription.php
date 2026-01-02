<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsletterSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'blog_id',
        'frequency',
        'visitor_id',
        'send_time',
        'send_day',
    ];

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    public function newsletterLogs(): HasMany
    {
        return $this->hasMany(NewsletterLog::class, 'newsletter_subscription_id');
    }
}
