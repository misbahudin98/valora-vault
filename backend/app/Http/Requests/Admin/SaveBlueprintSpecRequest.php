<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveBlueprintSpecRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20'],
            'slug' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/'],
            'title' => ['required', 'string', 'max:150'],
            'module' => ['required', 'string', 'max:100'],
            'actor' => ['required', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:Draft,Review,Approved,Implemented'],
            'user_story' => ['nullable', 'array'],
            'entities' => ['nullable', 'array'],
            'rbac' => ['nullable', 'array'],
            'endpoints' => ['nullable', 'array'],
            'acceptance_criteria' => ['nullable', 'array'],
            'raw_markdown' => ['nullable', 'string'],
        ];
    }
}
