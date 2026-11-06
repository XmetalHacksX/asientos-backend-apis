<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Node extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'identifier',
        'pos_x',
        'pos_y',
        'status',
        'is_occupied',
    ];

    protected function casts(): array
    {
        return [
            'pos_x' => 'integer',
            'pos_y' => 'integer',
            'is_occupied' => 'boolean',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
