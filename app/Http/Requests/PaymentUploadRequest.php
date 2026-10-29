<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_proof.required' => 'Bukti pembayaran wajib di-upload.',
            'payment_proof.mimes' => 'Format file yang diperbolehkan: jpeg, png, jpg, pdf.',
            'payment_proof.max' => 'Ukuran file maksimal 5 MB.',
        ];
    }
}
