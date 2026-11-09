<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable'],
            'is_active' => ['nullable', 'boolean'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'github_url' => ['nullable', 'string', 'max:255'],
            'linkedin_url' => ['nullable', 'string', 'max:255'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:100'],
            'allowed_features' => ['nullable', 'array'],
            'allowed_features.*' => ['string', 'max:100'],
        ];
    }

    /**
     * Configure additional validation hooks.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->has('role') && !is_null($this->input('role'))) {
                    $roles = $this->input('role');
                    if (is_string($roles)) {
                        $roles = array_map('trim', explode(',', $roles));
                    }
                    if (!is_array($roles)) {
                        $validator->errors()->add('role', 'Peran yang dipilih tidak valid atau belum terdaftar.');
                        return;
                    }
                    $configuredRoles = array_keys(config('permissions.roles', []));
                    $invalidRoles = array_diff($roles, $configuredRoles);
                    if (empty($roles) || !empty($invalidRoles)) {
                        $validator->errors()->add('role', 'Peran yang dipilih tidak valid atau belum terdaftar.');
                    }
                }
            }
        ];
    }

    /**
     * Extract sanitized and validated roles array.
     *
     * @return array<int, string>
     */
    public function getValidatedRoles(): array
    {
        $roles = $this->input('role', ['admin']);
        if (is_string($roles)) {
            $roles = array_map('trim', explode(',', $roles));
        }
        $roles = is_array($roles) ? array_values(array_unique(array_filter($roles))) : ['admin'];
        return empty($roles) ? ['admin'] : $roles;
    }
}
