<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\SeatStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeatRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::enum(SeatStatus::class)],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'guest_info' => ['nullable', 'array'],
        ];
    }
}
