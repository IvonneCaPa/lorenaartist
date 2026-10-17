<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'series' => ['required', 'string', 'max:20'],
            'invoice_type' => ['required', 'in:F1,F2,R1,R2,R3,R4,R5'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'rectified_invoice_id' => ['nullable', 'exists:invoices,id'],
            'rectification_reason' => ['nullable', 'string', 'in:01,02,03,04'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.concept' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.vat_rate' => ['required', 'numeric', 'in:0,4,10,21'],
        ];
    }
}
