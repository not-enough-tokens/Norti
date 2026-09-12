<?php

namespace App\Http\Requests\MarketData;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
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
            'symbol' => ['required', 'string', 'max:20'],
            'exchange' => ['sometimes', 'string', 'max:50'],
            'country' => ['sometimes', 'string', 'max:56'],
            'mic_code' => ['sometimes', 'string', 'max:10'],
        ];
    }
}
