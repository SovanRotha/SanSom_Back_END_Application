<?php

namespace App\Models\Category;

use App\Models\Account\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "account_id",
        "category_id",
        "type",
        "amount",
        "description",
        "transaction_date",
        "notes",
        "source",
        "status",
        "transfer_group_id",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function transactionAttachment()
    {
        return $this->hasMany(TransactionAttachment::class);
    }
}
