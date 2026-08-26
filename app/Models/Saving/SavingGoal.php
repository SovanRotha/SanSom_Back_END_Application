<?php

namespace App\Models\Saving;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name', 
        'description',
        'target_amount',
        'current_amount',
        'target_date',
        'icon',
        'color',
        'auto_allocate',
        'allocation_percentage',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function savingContribution()
    {
        return $this->hasMany(SavingContribution::class);
    }

    
}
