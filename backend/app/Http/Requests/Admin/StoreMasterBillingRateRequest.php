<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterBillingRateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'rate_key' => ['nullable', 'string', 'max:100', 'unique:master_billing_rates,rate_key', 'regex:/^[a-z0-9_]+$/'],
            'category' => ['nullable', 'string', 'in:system_core,menu_work,registered_module,custom'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'has_create' => ['boolean'],
            'has_read' => ['boolean'],
            'has_update' => ['boolean'],
            'has_delete' => ['boolean'],
            'has_grafik' => ['boolean'],
            'has_box_dashboard' => ['boolean'],
            'has_import' => ['boolean'],
            'has_export' => ['boolean'],
            'has_filter' => ['boolean'],
            'has_print' => ['boolean'],
            'has_notification' => ['boolean'],
            'has_custom_logic' => ['boolean'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama modul menu terdaftar wajib diisi.',
            'price.required' => 'Nominal tarif wajib diisi.',
            'price.numeric' => 'Nominal tarif harus berupa angka.',
            'price.min' => 'Nominal tarif tidak boleh bernilai negatif.',
            'rate_key.unique' => 'Kunci identifikasi tarif sudah digunakan. Gunakan kunci lain.',
            'rate_key.regex' => 'Kunci identifikasi hanya boleh berisi huruf kecil, angka, dan garis bawah (_).',
        ];
    }
}
