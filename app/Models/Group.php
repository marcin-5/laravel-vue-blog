<?php

namespace App\Models;

use App\Viewable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;
    use Viewable;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'content',
        'footer',
        'theme',
        'sidebar',
        'page_size',
        'is_published',
        'locale',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sidebar' => 'integer',
        'page_size' => 'integer',
        'theme' => 'array',
    ];

    /**
     * Właściciel grupy.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Członkowie grupy.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user')
            ->using(GroupMember::class)
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Posty w grupie.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
