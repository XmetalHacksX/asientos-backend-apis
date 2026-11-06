<?php

namespace App\Http\Requests\Api\V1\Ticket;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ValidateTicketRequest extends FormRequest
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
            'ticket_token' => ['required', 'string', 'uuid'],
        ];
    }
}
