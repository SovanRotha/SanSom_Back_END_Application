<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Budget\Budget;
use App\Models\Category\Category;
use App\Models\Category\Transaction;
use App\Models\Saving\SavingGoal;
use App\Models\User\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "name",
        "role_id",
        "email",
        "password",
        "phone",
        "profile",
        "currency",
        "last_login_at",
        "updated_at",
        "deleted_at",
        "email_verified_at"
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function category()
    {
        return $this->hasMany(Category::class);
    }

    public function transaction()
    {
        return $this->hasMany(Transaction::class);
    }

    public function budget()
    {
        return $this->hasMany(Budget::class);
    }

    public function savingGoal()
    {
        return $this->hasMany(SavingGoal::class);
    }
}
