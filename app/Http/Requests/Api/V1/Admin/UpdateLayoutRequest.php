<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLayoutRequest extends FormRequest
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
            'viewport' => ['nullable', 'array'],
            'nodes' => ['nullable', 'array'],
            'nodes.*.identifier' => ['required', 'string', 'max:50'],
            'nodes.*.pos_x' => ['required', 'integer'],
            'nodes.*.pos_y' => ['required', 'integer'],
            'nodes.*.status' => ['nullable', 'string', 'in:active,maintenance,blocked'],
            'layout_objects' => ['nullable', 'array'],
            'layout_objects.*.type' => ['required', 'string'],
            'layout_objects.*.properties' => ['required', 'array'],
        ];
    }
}
