<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NodeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'space_id' => $this->space_id,
            'identifier' => $this->identifier,
            'pos_x' => $this->pos_x,
            'pos_y' => $this->pos_y,
            'status' => $this->status,
            'is_occupied' => (bool) $this->is_occupied,
        ];
    }
}
