<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ticket\ValidateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Services\TicketValidatorService;
use Illuminate\Http\JsonResponse;

class TicketScanController extends Controller
{
    public function __construct(
        protected TicketValidatorService $validatorService
    ) {}

    /**
     * Scan and validate a ticket token at event door.
     */
    public function validateToken(ValidateTicketRequest $request): JsonResponse
    {
        $result = $this->validatorService->validateTicket($request->ticket_token);

        $httpCode = match ($result['status']) {
            'ingresado' => 200,
            'already_used' => 409,
            'not_found' => 404,
            default => 422,
        };

        return response()->json([
            'valid' => $result['valid'],
            'status' => $result['status'],
            'message' => $result['message'],
            'validated_at' => $result['validated_at'],
            'ticket' => $result['reservation'] ? new TicketResource($result['reservation']) : null,
        ], $httpCode);
    }
}
