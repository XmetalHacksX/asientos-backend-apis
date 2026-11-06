<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\BuildingRequest;
use App\Http\Resources\BuildingResource;
use App\Models\Building;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BuildingController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return BuildingResource::collection(Building::with(['campus', 'spaces'])->get());
    }

    public function store(BuildingRequest $request): JsonResponse
    {
        $building = Building::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Edificio creado exitosamente.',
            'data' => new BuildingResource($building->load('campus')),
        ], 201);
    }

    public function show(Building $building): BuildingResource
    {
        return new BuildingResource($building->load(['campus', 'spaces.nodes', 'spaces.layoutObjects']));
    }

    public function update(BuildingRequest $request, Building $building): JsonResponse
    {
        $building->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Edificio actualizado exitosamente.',
            'data' => new BuildingResource($building->load('campus')),
        ]);
    }

    public function destroy(Building $building): JsonResponse
    {
        $building->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Edificio eliminado correctamente.',
        ]);
    }
}
