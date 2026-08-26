<?php

namespace App\Models\Budget;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'month',
        'total_limit',
        'rollover_enabled',
        'rollover_amount',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
}
