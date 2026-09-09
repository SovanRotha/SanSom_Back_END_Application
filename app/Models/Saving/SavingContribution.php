<?php

namespace App\Models\Saving;

use App\Models\Category\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'saving_goal_id',
        'transaction_id',
        'amount',
        'contribution',
        'note'
    ];

    public function savingGoal()
    {
        return $this->belongsTo(SavingGoal::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
