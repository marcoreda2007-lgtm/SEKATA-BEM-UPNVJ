<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    public const TYPE_BOOKING_CREATED = 'booking_created';
    public const TYPE_BOOKING_APPROVED = 'booking_approved';
    public const TYPE_BOOKING_REJECTED = 'booking_rejected';
    public const TYPE_PROOF_REVISION_REQUESTED = 'proof_revision_requested';
    public const TYPE_PROOF_COMPLETED = 'proof_completed';

    protected $table = 'notifications';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'booking_id',
        'type',
        'title',
        'message',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'booking_id' => 'integer',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    public function detailUrl(): string
    {
        return route('peminjaman.show', $this->booking_id);
    }
}