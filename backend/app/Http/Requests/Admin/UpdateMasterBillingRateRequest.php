<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMasterBillingRateRequest extends FormRequest
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
            'price' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'in:system_core,menu_work,registered_module,custom'],
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
            'price.required' => 'Nominal tarif standar wajib diisi.',
            'price.numeric' => 'Nominal tarif harus berupa angka.',
            'price.min' => 'Nominal tarif tidak boleh bernilai negatif.',
            'reason.required' => 'Keterangan alasan perubahan tarif wajib disertakan sesuai standar transparansi.',
            'reason.min' => 'Alasan perubahan tarif minimal berisi 5 karakter.',
        ];
    }
}
