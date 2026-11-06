<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpaceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'building_id' => $this->building_id,
            'building' => new BuildingResource($this->whenLoaded('building')),
            'name' => $this->name,
            'viewport' => $this->viewport,
            'is_template' => $this->is_template,
            'nodes' => NodeResource::collection($this->whenLoaded('nodes')),
            'layout_objects' => LayoutObjectResource::collection($this->whenLoaded('layoutObjects')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
