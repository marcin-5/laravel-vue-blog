<?php

namespace App\Models;

use Database\Factories\PrivateConversationParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateConversationParticipant extends Model
{
    /** @use HasFactory<PrivateConversationParticipantFactory> */
    use HasFactory;

    protected $fillable = [
        'private_conversation_id',
        'user_id',
        'email_notifications',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_notifications' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(PrivateConversation::class, 'private_conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
