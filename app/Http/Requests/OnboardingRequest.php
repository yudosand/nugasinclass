<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'client' && $this->user()->isApproved();
    }

    public function rules(): array
    {
        $hasAccess = \App\Support\ElearningSchema::ready() && \App\Models\ElearningCredential::where('user_id', $this->user()->id)->exists();
        return [
            'universitas' => 'required|string|max:255',
            'nim' => 'required|string|max:50',
            'program_studi' => 'required|string|max:255',
            'semester' => 'required|integer|min:1|max:14',
            'detail_akademik' => 'nullable|string|max:5000',
            'agree_terms' => 'required|accepted',
            'elearning_login' => [$hasAccess ? 'nullable' : 'required', 'required_with:elearning_password', 'string', 'max:255'],
            'elearning_password' => [$hasAccess ? 'nullable' : 'required', 'required_with:elearning_login', 'string', 'max:1024'],
        ];
    }

    public function messages(): array
    {
        return [
            'universitas.required' => 'Nama Universitas / Perguruan Tinggi wajib diisi.',
            'nim.required' => 'NIM (Nomor Induk Mahasiswa) wajib diisi.',
            'program_studi.required' => 'Program Studi / Jurusan wajib diisi.',
            'semester.required' => 'Semester saat ini wajib diisi.',
            'semester.integer' => 'Semester harus berupa angka.',
            'agree_terms.required' => 'Anda wajib menyetujui Syarat & Ketentuan akses e-learning.',
            'agree_terms.accepted' => 'Anda wajib menyetujui Syarat & Ketentuan akses e-learning.',
        ];
    }
}
