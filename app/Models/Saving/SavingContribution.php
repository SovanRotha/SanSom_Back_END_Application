<?php

namespace App\Models\Saving;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'saving_goal_id',
        'transaction_id',
        'amount',
        'contribution_date',
        'note'
    ];

    public function savingGoal()
    {
        return $this->belongsTo(SavingGoal::class);
    }
}
