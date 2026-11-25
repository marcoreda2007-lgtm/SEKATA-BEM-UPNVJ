<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingProof extends Model
{
    protected $fillable = [
        'booking_id',
        'category',
        'path',
        'original_name',
        'mime_type',
        'size',
        'uploaded_at',
        'requires_replacement',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'requires_replacement' => 'boolean',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
