<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingProof extends Model
{
    protected $table = 'booking_proofs';

    protected $primaryKey = 'id';

    public $timestamps = true;

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
            'id' => 'integer',
            'booking_id' => 'integer',
            'size' => 'integer',
            'uploaded_at' => 'datetime',
            'requires_replacement' => 'boolean',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}