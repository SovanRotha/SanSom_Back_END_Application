<?php

namespace App\Models\AI;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIActionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'conversation_id',
        'ai_conversations',
        'message_id',
        'action',
        'parameters',
        'status',
        'confirmed_at',
        'executed_at' 
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conversation()
    {
        return $this->belongsTo(AIConversation::class);
    }

    public function message()
    {
        return $this->belongsTo(AIMessage::class);
    }
}
