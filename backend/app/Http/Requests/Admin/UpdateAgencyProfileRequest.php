<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgencyProfileRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'agency_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'hotline_wa' => ['required', 'string', 'max:50'],
            'operational_address' => ['required', 'string', 'max:1000'],
            'github_url' => ['nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['required', 'string', 'max:100'],
            'bank_account_no' => ['required', 'string', 'max:100'],
            'bank_account_name' => ['required', 'string', 'max:255'],
            'secondary_bank_name' => ['nullable', 'string', 'max:100'],
            'secondary_bank_account_no' => ['nullable', 'string', 'max:100'],
            'secondary_bank_account_name' => ['nullable', 'string', 'max:255'],
            'signatory_name' => ['required', 'string', 'max:255'],
            'signatory_title' => ['required', 'string', 'max:255'],
            'terms_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
