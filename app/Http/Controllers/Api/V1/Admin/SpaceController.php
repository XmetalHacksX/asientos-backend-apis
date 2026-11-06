<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\SpaceRequest;
use App\Http\Resources\SpaceResource;
use App\Models\Space;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SpaceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SpaceResource::collection(Space::with(['building.campus', 'nodes', 'layoutObjects'])->get());
    }

    public function store(SpaceRequest $request): JsonResponse
    {
        $space = Space::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Espacio/Sala creada exitosamente.',
            'data' => new SpaceResource($space->load('building.campus')),
        ], 201);
    }

    public function show(Space $space): SpaceResource
    {
        return new SpaceResource($space->load(['building.campus', 'nodes', 'layoutObjects']));
    }

    public function update(SpaceRequest $request, Space $space): JsonResponse
    {
        $space->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Espacio actualizado exitosamente.',
            'data' => new SpaceResource($space->load(['building.campus', 'nodes', 'layoutObjects'])),
        ]);
    }

    public function destroy(Space $space): JsonResponse
    {
        $space->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Espacio eliminado correctamente.',
        ]);
    }
}
