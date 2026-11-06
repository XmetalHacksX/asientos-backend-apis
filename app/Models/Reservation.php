<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'node_id',
        'event_seat_id',
        'user_id',
        'reserved_by_user_id',
        'guest_info',
        'status',
        'expires_at',
        'ticket_token',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'guest_info' => 'array',
            'expires_at' => 'datetime',
            'validated_at' => 'datetime',
            'status' => ReservationStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Reservation $reservation) {
            if (empty($reservation->ticket_token)) {
                $reservation->ticket_token = (string) Str::uuid();
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function eventSeat(): BelongsTo
    {
        return $this->belongsTo(EventSeat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reservedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by_user_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
