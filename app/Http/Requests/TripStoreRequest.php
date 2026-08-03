<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TripStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'passenger_id' => ['required', 'integer', 'exists:users,id'],

            'pricing_type' => ['required', 'string', 'in:fixed,calculated'],

            'estimated_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
