<?php

namespace App\Models\Category;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        "name", 
        "type",
        "user_id",
        "parent_id",
        "icon",
        "color",
        "is_system"
    ];
}
