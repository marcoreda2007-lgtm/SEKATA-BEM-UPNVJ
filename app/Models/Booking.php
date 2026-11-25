<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'room_id',
        'tanggal',
        'start_time',
        'end_time',
        'activity_description',
        'pic_name',
        'pic_division',
        'status',
        'decision_reason',
        'inspection_note',
        'decided_by',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'decided_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(BookingProof::class);
    }
}
