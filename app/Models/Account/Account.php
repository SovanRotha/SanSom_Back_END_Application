<?php

namespace App\Models\Account;

use App\Models\Bill\Bill;
use App\Models\Category\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // AccountType is not imported with a use statement because it lives in
    // the same namespace as this class (App\Models\Account), so PHP
    // resolves it automatically.
    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class, 'account_type_id');
    }

    public function transaction(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function bill(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
}