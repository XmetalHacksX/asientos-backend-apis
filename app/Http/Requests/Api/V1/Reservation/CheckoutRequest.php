<?php

namespace App\Http\Requests\Api\V1\Reservation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
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
            'reservation_id' => ['required_without:ticket_token', 'nullable', 'integer', 'exists:reservations,id'],
            'ticket_token' => ['required_without:reservation_id', 'nullable', 'string'],
            'payment_method' => ['nullable', 'string', 'in:stripe,direct,cash'],
            'payment_intent_id' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'guest_info' => ['nullable', 'array'],
            'guest_info.name' => ['nullable', 'string', 'max:255'],
            'guest_info.email' => ['nullable', 'email'],
            'guest_info.phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
