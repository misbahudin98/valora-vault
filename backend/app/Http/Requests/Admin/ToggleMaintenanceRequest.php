<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ToggleMaintenanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || ($this->user()?->hasPermission('settings.manage') ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['nullable', 'string', 'in:enable,disable'],
            'secret' => ['nullable', 'string', 'max:64'],
            'retry' => ['nullable', 'integer', 'min:5', 'max:86400'],
        ];
    }

    /**
     * Resolve action to perform.
     */
    public function getAction(bool $isCurrentlyDown): string
    {
        return (string) $this->input('action', $isCurrentlyDown ? 'disable' : 'enable');
    }

    /**
     * Resolve secret bypass token.
     */
    public function getSecret(): string
    {
        return (string) ($this->input('secret') ?: Str::random(16));
    }

    /**
     * Resolve retry interval in seconds.
     */
    public function getRetry(): int
    {
        return (int) $this->input('retry', 60);
    }
}
