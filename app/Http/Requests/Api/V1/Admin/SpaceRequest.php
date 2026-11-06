<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SpaceRequest extends FormRequest
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
            'building_id' => ['required', 'integer', 'exists:buildings,id'],
            'name' => ['required', 'string', 'max:255'],
            'viewport' => ['nullable', 'array'],
            'is_template' => ['nullable', 'boolean'],
        ];
    }
}
