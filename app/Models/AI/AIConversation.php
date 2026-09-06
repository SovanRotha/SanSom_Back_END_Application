<?php

namespace App\Models\AI;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AIConversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ai_conversations';

    protected $fillable = [
        'user_id',
        'title',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actionLog()
    {
        return $this->hasMany(AIActionLog::class, 'conversation_id');
    }

    public function messages()
    {
        return $this->hasMany(AIMessage::class, 'conversation_id');
    }

    public function message()
    {
        return $this->messages();
    }
}
