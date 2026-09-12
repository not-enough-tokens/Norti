<?php

namespace App\Http\Requests\MarketData;

use App\Services\TwelveData\TwelveDataClient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TimeSeriesRequest extends FormRequest
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
            'interval' => ['required', 'string', Rule::in(TwelveDataClient::INTERVALS)],
            'outputsize' => ['sometimes', 'integer', 'between:1,5000'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date'],
            'timezone' => ['sometimes', 'string', 'max:64'],
            'order' => ['sometimes', 'string', Rule::in(['ASC', 'DESC', 'asc', 'desc'])],
            'exchange' => ['sometimes', 'string', 'max:50'],
            'country' => ['sometimes', 'string', 'max:56'],
        ];
    }
}
