<?php

namespace App\Models;

use Database\Factories\PrivateConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PrivateConversation extends Model
{
    /** @use HasFactory<PrivateConversationFactory> */
    use HasFactory;

    protected $fillable = [
        'blog_id',
        'group_id',
        'post_id',
        'initiator_id',
        'owner_id',
        'subject',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PrivateMessage::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(PrivateConversationParticipant::class);
    }

    public function participantUsers(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            PrivateConversationParticipant::class,
            'private_conversation_id',
            'id',
            'id',
            'user_id',
        );
    }
}
