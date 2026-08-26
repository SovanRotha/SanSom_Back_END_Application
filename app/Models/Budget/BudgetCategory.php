<?php

namespace App\Models\Budget;

use App\Models\Category\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'category_id',
        'limit_amount',
        'alert_percentage',
        'rollover_amount',
    ];

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
