<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\CampusRequest;
use App\Http\Resources\CampusResource;
use App\Models\Campus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CampusController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CampusResource::collection(Campus::with('buildings')->get());
    }

    public function store(CampusRequest $request): JsonResponse
    {
        $campus = Campus::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Campus creado exitosamente.',
            'data' => new CampusResource($campus),
        ], 201);
    }

    public function show(Campus $campus): CampusResource
    {
        return new CampusResource($campus->load('buildings.spaces'));
    }

    public function update(CampusRequest $request, Campus $campus): JsonResponse
    {
        $campus->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Campus actualizado exitosamente.',
            'data' => new CampusResource($campus),
        ]);
    }

    public function destroy(Campus $campus): JsonResponse
    {
        $campus->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Campus eliminado correctamente.',
        ]);
    }
}
