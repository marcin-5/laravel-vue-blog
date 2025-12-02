<?php

namespace App;

use App\Models\PageView;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;
use Redis;

/**
 * @mixin Model
 */
trait Viewable
{
    /**
     * @throws ClassMorphViolationException
     * @throws InvalidArgumentException
     */
    public function getViewCountAttribute(): int
    {
        $key = $this->getViewableCacheKey();

        if (!class_exists(Redis::class)) {
            return $this->pageViews()->count();
        }

        return (int)Cache::store('redis')->get($key, function () use ($key) {
            $count = $this->pageViews()->count();
            Cache::store('redis')->forever($key, $count);

            return $count;
        });
    }

    /**
     * @throws ClassMorphViolationException
     */
    public function getViewableCacheKey(): string
    {
        return sprintf(
            'page_views:count:%s:%d',
            $this->getMorphClass(),
            $this->getKey(),
        );
    }

    public function pageViews(): MorphMany
    {
        return $this->morphMany(PageView::class, 'viewable');
    }
}
