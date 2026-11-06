<?php

namespace App\Http\Requests\Api\V1\Reservation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class HoldSeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'seat_id' => ['required', 'integer', 'exists:event_seats,id'],
            'guest_info' => ['nullable', 'array'],
            'guest_info.name' => ['nullable', 'string', 'max:255'],
            'guest_info.email' => ['nullable', 'email'],
            'guest_info.phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
