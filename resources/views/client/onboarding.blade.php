@extends('layouts.secure')

@section('title', 'Lengkapi Data Akademik - Nugasin Class')

@section('content')
<main class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 p-md-5">
          <div class="text-center mb-4">
            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 mb-2">Langkah Terakhir Onboarding</span>
            <h1 class="h3 fw-bold mb-1">Lengkapi Profil Akademik Kamu</h1>
            <p class="text-secondary">Data ini digunakan tim Nugasin untuk memetakan tugas, diskusi, dan mata kuliahmu.</p>
          </div>

          @if($errors->any())
            <div class="alert alert-danger rounded-3">
              <ul class="mb-0">
                @foreach($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          @if(!$accessReady)
            <div class="alert alert-warning">Form belum tersedia. Silakan hubungi admin untuk mengaktifkan penyimpanan akses e-learning.</div>
          @endif
          <form action="{{ route('onboarding.store') }}" method="POST" id="onboarding-form">
            @csrf

            <div class="row g-3 mb-4">
              <div class="col-12 col-md-6">
                <label for="universitas" class="form-label fw-semibold">Universitas / Perguruan Tinggi</label>
                <input type="text" class="form-control rounded-3" id="universitas" name="universitas" value="{{ old('universitas', $profile->universitas ?? '') }}" placeholder="Contoh: Universitas Terbuka" required>
              </div>

              <div class="col-12 col-md-6">
                <label for="nim" class="form-label fw-semibold">NIM (Nomor Induk Mahasiswa)</label>
                <input type="text" class="form-control rounded-3" id="nim" name="nim" value="{{ old('nim', $profile->nim ?? '') }}" placeholder="Contoh: 041234567" required>
              </div>

              <div class="col-12 col-md-6">
                <label for="program_studi" class="form-label fw-semibold">Program Studi / Jurusan</label>
                <input type="text" class="form-control rounded-3" id="program_studi" name="program_studi" value="{{ old('program_studi', $profile->program_studi ?? '') }}" placeholder="Contoh: Sistem Informasi" required>
              </div>

              <div class="col-12 col-md-6">
                <label for="semester" class="form-label fw-semibold">Semester Saat Ini</label>
                <select class="form-select rounded-3" id="semester" name="semester" required>
                  <option value="">-- Pilih Semester --</option>
                  @for($i = 1; $i <= 14; $i++)
                    <option value="{{ $i }}" {{ old('semester', $profile->semester ?? '') == $i ? 'selected' : '' }}>Semester {{ $i }}</option>
                  @endfor
                </select>
              </div>

              <div class="col-12">
                <label for="detail_akademik" class="form-label fw-semibold">Data Akademik Lainnya (Opsional)</label>
                <textarea class="form-control rounded-3" id="detail_akademik" name="detail_akademik" rows="3" maxlength="5000" placeholder="Catatan akademik. Jangan masukkan password di sini.">{{ old('detail_akademik', $profile->detail_akademik ?? '') }}</textarea>
              </div>
            </div>

            <div class="p-4 border rounded-4 bg-body-tertiary mb-4">
              <h6 class="fw-bold mb-2">Syarat & Ketentuan Akses E-Learning</h6>
              <p class="small text-secondary mb-3">
                Dengan melanjutkan, Anda secara penuh menyetujui untuk memberikan wewenang dan hak akses sistem e-learning universitas kepada tim profesional Nugasin Class untuk keperluan pendampingan, absensi sesi, pengerjaan diskusi, dan pengerjaan tugas akademik sesuai paket yang diambil.
              </p>

              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="agree_terms" id="agree_terms" value="1" @checked(old('agree_terms')) required aria-controls="elearning-fields">
                <label class="form-check-label fw-semibold" for="agree_terms">
                  Saya menyetujui Syarat & Ketentuan di atas dan menjamin keabsahan data akademik yang saya berikan.
                </label>
              </div>
            </div>

            @if($accessReady)
            <fieldset id="elearning-fields" class="border rounded-4 p-4 mb-4" hidden disabled data-has-access="{{ $hasAccess ? '1' : '0' }}">
              <legend class="float-none w-auto px-2 fs-5 fw-bold">Akses E-Learning</legend>
              <p class="small text-secondary">Isi akun e-learning kampus Anda. Data hanya dapat dibuka admin melalui verifikasi tambahan.</p>
              @if($hasAccess)<p class="alert alert-info small">Akses sebelumnya sudah tersimpan. Kosongkan kedua kolom untuk mempertahankannya, atau isi keduanya untuk memperbarui akses.</p>@endif
              <label for="elearning_login" class="form-label fw-semibold">NIM / Email E-Learning</label>
              <input class="form-control mb-3" id="elearning_login" name="elearning_login" maxlength="255" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="NIM atau email untuk masuk e-learning">
              <label for="elearning_password" class="form-label fw-semibold">Password E-Learning</label>
              <input class="form-control" id="elearning_password" name="elearning_password" type="password" maxlength="1024" autocomplete="off" spellcheck="false">
              <p class="small text-secondary mt-2 mb-0">Password tidak ditampilkan kembali setelah disimpan. Jika formulir gagal, isi ulang password.</p>
            </fieldset>
            <noscript><p class="alert alert-warning">Aktifkan JavaScript agar form akses muncul setelah persetujuan dicentang.</p></noscript>
            @endif

            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold" @disabled(!$accessReady)>Simpan & Masuk Dashboard →</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection
@push('scripts')
<script src="{{ asset('assets/js/onboarding-access.js') }}" defer></script>
@endpush
