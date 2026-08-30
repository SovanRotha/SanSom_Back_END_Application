<?php

namespace App\Models;

use App\Http\Controllers\Account\AccountTypeController;
use App\Models\Bill\Bill;
use App\Models\Category\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_type_id',
        'name',
        'balance',
        'currency',
        'icon',
        'color',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    /**
     * Account belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Account belongs to an account type.
     */
    public function accountType(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Account\AccountType::class);
    }

    public function transaction()
    {
        return $this->hasMany(Transaction::class);
    }

    public function bill()
    {
        return $this->hasMany(Bill::class);
    }
}