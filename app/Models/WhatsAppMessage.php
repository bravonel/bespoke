<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessage extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'user_id',
        'ai_assistant_message_id',
        'provider_message_id',
        'direction',
        'from_phone',
        'to_phone',
        'message_type',
        'body',
        'status',
        'metadata',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aiAssistantMessage(): BelongsTo
    {
        return $this->belongsTo(AiAssistantMessage::class);
    }
}
