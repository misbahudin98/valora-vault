<?php

declare(strict_types=1);

namespace App\Http\Requests\Telemetry;

use Illuminate\Foundation\Http\FormRequest;

class ReportClientErrorRequest extends FormRequest
{
    /**
     * Public telemetry ingest endpoint (always allowed; throttled at route level).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'error_id' => ['nullable', 'string', 'max:64'],
            'message' => ['required', 'string', 'max:1000'],
            'stack' => ['nullable', 'string', 'max:5000'],
            'url' => ['nullable', 'string', 'max:500'],
            'user_agent' => ['nullable', 'string', 'max:500'],
            'timestamp' => ['nullable', 'string', 'max:50'],
        ];
    }
}
