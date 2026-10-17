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
        $isRectifying = str_starts_with($this->input('invoice_type', ''), 'R');

        return [
            'series' => ['required', 'string', 'max:20'],
            'invoice_type' => ['required', 'in:F1,F2,R1,R2,R3,R4,R5'],
            'client_id' => ['nullable', 'exists:clients,id'],
            
            // Si es rectificativa, estos dos campos son estrictamente obligatorios por AEAT:
            'rectified_invoice_id' => [$isRectifying ? 'required' : 'nullable', 'exists:invoices,id'],
            'rectification_reason' => [$isRectifying ? 'required' : 'nullable', 'string', 'in:01,02,03,04'],
            
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.concept' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric'],
            // Si es rectificativa se permiten importes negativos; si es ordinaria, solo positivos:
            'lines.*.unit_price' => ['required', 'numeric', $isRectifying ? 'nullable' : 'min:0'],
            'lines.*.vat_rate' => ['required', 'numeric', 'in:0,4,10,21'],
        ];
    }
}
