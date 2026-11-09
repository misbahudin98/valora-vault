<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\ClientProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreClientProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user !== null && ($user->isAdmin() || $user->hasPermission('projects.create'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $mode = $this->input('mode', 'new_client');

        $rules = [
            'mode' => ['required', 'string', 'in:new_client,existing_client'],
            'app_name' => ['required', 'string', 'max:255'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'project_type' => ['required', 'string', 'in:development,maintenance'],
            'lead_pic' => ['nullable', 'string', 'max:255'],
            'lead_pic_id' => ['nullable', 'integer', 'exists:users,id'],
            'worker_ids' => ['nullable', 'array'],
            'worker_ids.*' => ['integer', 'exists:users,id'],
            'github_url' => ['nullable', 'string', 'max:255'],
            'app_desc' => ['nullable', 'string'],
            'use_landing' => ['boolean'],
            'use_login' => ['boolean'],
            'use_register' => ['boolean'],
            'use_app_core' => ['boolean'],
            'landing_price' => ['numeric', 'min:0'],
            'login_price' => ['numeric', 'min:0'],
            'register_price' => ['numeric', 'min:0'],
            'landing_note' => ['nullable', 'string', 'max:255'],
            'login_note' => ['nullable', 'string', 'max:255'],
            'register_note' => ['nullable', 'string', 'max:255'],
            'landing_capabilities' => ['nullable', 'array'],
            'login_capabilities' => ['nullable', 'array'],
            'register_capabilities' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'in:Aktif,Selesai,Pending'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'max:100'],
        ];

        if ($mode === 'existing_client') {
            $rules['client_id'] = ['required', 'integer', 'exists:clients,id'];
        } else {
            $rules['name'] = ['required', 'string', 'max:255'];
            $rules['contact_person'] = ['nullable', 'string', 'max:255'];
            $rules['phone'] = ['nullable', 'string', 'max:50'];
            $rules['email'] = ['nullable', 'email', 'max:255'];
            $rules['address'] = ['nullable', 'string'];
            $rules['industry'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
