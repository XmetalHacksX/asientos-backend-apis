<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateLayoutRequest;
use App\Http\Resources\SpaceResource;
use App\Models\LayoutObject;
use App\Models\Node;
use App\Models\Space;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SpaceLayoutController extends Controller
{
    /**
     * Bulk synchronize nodes (seats template) and layout objects (stage, doors, screens) for a space.
     */
    public function updateLayout(UpdateLayoutRequest $request, Space $space): JsonResponse
    {
        DB::transaction(function () use ($request, $space) {
            // Update viewport if provided
            if ($request->has('viewport')) {
                $space->update(['viewport' => $request->viewport]);
            }

            // Sync nodes if provided
            if ($request->has('nodes')) {
                $space->nodes()->delete();

                $now = now();
                $nodesBatch = [];
                foreach ($request->nodes as $node) {
                    $nodesBatch[] = [
                        'space_id' => $space->id,
                        'identifier' => $node['identifier'],
                        'pos_x' => $node['pos_x'],
                        'pos_y' => $node['pos_y'],
                        'status' => $node['status'] ?? 'active',
                        'is_occupied' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (! empty($nodesBatch)) {
                    Node::insert($nodesBatch);
                }
            }

            // Sync layout objects if provided
            if ($request->has('layout_objects')) {
                $space->layoutObjects()->delete();

                $now = now();
                $objectsBatch = [];
                foreach ($request->layout_objects as $obj) {
                    $objectsBatch[] = [
                        'space_id' => $space->id,
                        'type' => $obj['type'],
                        'properties' => json_encode($obj['properties']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (! empty($objectsBatch)) {
                    LayoutObject::insert($objectsBatch);
                }
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Diseño del espacio (asientos y objetos de escenario) sincronizado exitosamente.',
            'data' => new SpaceResource($space->fresh(['building.campus', 'nodes', 'layoutObjects'])),
        ]);
    }
}
