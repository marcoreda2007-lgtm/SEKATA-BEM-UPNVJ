<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $table = 'rooms';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'location',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}