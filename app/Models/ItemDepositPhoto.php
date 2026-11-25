<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemDepositPhoto extends Model
{
    protected $table = 'item_deposit_photos';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'item_deposit_id' => 'integer',
            'size' => 'integer',
        ];
    }

    public function itemDeposit(): BelongsTo
    {
        return $this->belongsTo(ItemDeposit::class);
    }
}