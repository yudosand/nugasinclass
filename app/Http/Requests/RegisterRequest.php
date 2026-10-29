<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'nomor_hp' => 'required|string|max:20|unique:users,nomor_hp',
            'password' => 'required|string|min:6|confirmed',
            'package_id' => 'required|exists:packages,id',
            'terms_accepted' => ['required', 'accepted'],
            'terms_version' => ['required', \Illuminate\Validation\Rule::in([\App\Support\ServiceTerms::VERSION])],
            'referral_code' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9][A-Za-z0-9_-]*$/D'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'terms_accepted.required' => 'Baca dan setujui Syarat dan Ketentuan untuk melanjutkan pendaftaran.',
            'terms_accepted.accepted' => 'Baca dan setujui Syarat dan Ketentuan untuk melanjutkan pendaftaran.',
            'terms_version.required' => 'Muat ulang halaman dan baca Syarat dan Ketentuan yang berlaku.',
            'terms_version.in' => 'Syarat dan Ketentuan telah diperbarui. Muat ulang halaman dan baca kembali sebelum menyetujui.',
            'nomor_hp.required' => 'Nomor HP wajib diisi.',
            'nomor_hp.unique' => 'Nomor HP sudah terdaftar. Silakan login.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'package_id.required' => 'Pilih paket layanan yang kamu inginkan.',
            'package_id.exists' => 'Paket layanan yang dipilih tidak valid.',
        ];
    }
}
