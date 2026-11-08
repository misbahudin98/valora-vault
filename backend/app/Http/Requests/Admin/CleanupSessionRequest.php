<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\UserDevice;
use Illuminate\Foundation\Http\FormRequest;

class CleanupSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('cleanup', UserDevice::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'grace_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'force' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get grace minutes parameter with safe default.
     */
    public function getGraceMinutes(): int
    {
        return (int) $this->input('grace_minutes', 30);
    }

    /**
     * Get force deletion boolean flag.
     */
    public function isForce(): bool
    {
        return $this->boolean('force', false);
    }
}
