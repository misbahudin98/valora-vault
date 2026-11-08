<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FilterLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || ($this->user()?->hasPermission('reports.view') ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'level' => ['nullable', 'string', 'max:20'],
            'search' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }

    /**
     * Get target log level filter.
     */
    public function getFilterLevel(): ?string
    {
        $level = $this->input('level');
        return $level ? strtoupper(trim((string) $level)) : null;
    }

    /**
     * Get search term.
     */
    public function getSearchQuery(): ?string
    {
        $search = $this->input('search');
        return $search ? trim((string) $search) : null;
    }

    /**
     * Get safe limit ceiling.
     */
    public function getLimit(int $default = 100): int
    {
        return min((int) $this->input('limit', $default), 200);
    }
}
