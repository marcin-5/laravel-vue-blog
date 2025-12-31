<?php

namespace App\Models;

use Database\Factories\UserAgentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserAgent extends Model
{
    /** @use HasFactory<UserAgentFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    public function pageViews(): HasMany
    {
        return $this->hasMany(PageView::class);
    }
}
