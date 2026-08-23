<?php

namespace App\Models\Category;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
      "transaction_id",
      "file_name",
      "file_path",
      "file_size",
      "created_at",
      "updated_at",
    ];
}
