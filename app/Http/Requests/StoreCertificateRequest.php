<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCertificateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'training_record_id' => ['nullable', 'integer', 'exists:training_records,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'certificate_number' => ['nullable', 'string', 'max:100'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Certificates must be a PDF, JPG or PNG file.',
            'file.max' => 'Certificates must be 10MB or smaller.',
        ];
    }
}
