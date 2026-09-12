<?php

namespace App\Http\Requests\MarketData;

use App\Services\TwelveData\TwelveDataClient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuoteRequest extends FormRequest
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
            'interval' => ['sometimes', 'string', Rule::in(TwelveDataClient::INTERVALS)],
            'exchange' => ['sometimes', 'string', 'max:50'],
            'country' => ['sometimes', 'string', 'max:56'],
            'type' => ['sometimes', 'string', 'max:30'],
        ];
    }
}
