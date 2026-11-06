<?php

namespace App\Models;

use App\Enums\SeatStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EventSeat extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'identifier',
        'pos_x',
        'pos_y',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'pos_x' => 'integer',
            'pos_y' => 'integer',
            'status' => SeatStatus::class,
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reservation(): HasOne
    {
        return $this->hasOne(Reservation::class);
    }
}
