<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemDepositPhoto extends Model
{
    protected $fillable = ['path', 'original_name', 'mime_type', 'size'];

    public function itemDeposit(): BelongsTo
    {
        return $this->belongsTo(ItemDeposit::class);
    }
}
