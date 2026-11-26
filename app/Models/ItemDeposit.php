<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemDeposit extends Model
{
    protected $fillable = [
        'depositor_name',
        'item_name',
        'description',
        'deposited_on',
    ];

    protected function casts(): array
    {
        return [
            'deposited_on' => 'date',
            'collected_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ItemDepositPhoto::class);
    }
}
