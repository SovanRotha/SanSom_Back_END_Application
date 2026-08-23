<?php

namespace App\Models\Category;

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
        "created_at",
        "updated_at"
    ];
}
