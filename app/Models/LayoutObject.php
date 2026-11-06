<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LayoutObject extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'type',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }
}
