<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TripUpdateRequest extends FormRequest
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
            'driver_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'pricing_type' => [
                'sometimes',
                'string',
                'in:fixed,calculated',
            ],

            'estimated_price' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'final_price' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            'status' => [
                'sometimes',
                'string',
                'in:pending,accepted,in_progress,completed,cancelled',
            ],
        ];
    }
}
