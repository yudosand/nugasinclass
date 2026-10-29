@extends('layouts.app')

@section('title', 'Pendaftaran Client - Nugasin Class')

@section('content')
<main class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 p-md-5">
          <div class="text-center mb-4">
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-2">Pendaftaran Client</span>
            <h1 class="h3 fw-bold mb-1">Mulai Perjalanan Akademikmu</h1>
            <p class="text-secondary">Pilih paket layanan dan buat akun dengan nomor HP milikmu.</p>
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

          <form action="{{ route('register') }}" method="POST">
            @csrf

            <div class="row g-3 mb-4">
              <div class="col-12">
                <label for="name" class="form-label fw-semibold">Nama Lengkap</label>
                <input type="text" class="form-control rounded-3" id="name" name="name" value="{{ old('name') }}" placeholder="Masukkan nama lengkap" required>
              </div>

              <div class="col-12">
                <label for="nomor_hp" class="form-label fw-semibold">Nomor WhatsApp / HP (Aktif)</label>
                <div class="input-group">
                  <span class="input-group-text">📱</span>
                  <input type="text" class="form-control rounded-3" id="nomor_hp" name="nomor_hp" value="{{ old('nomor_hp') }}" placeholder="Contoh: 08123456789" required>
                </div>
                <div class="form-text">Nomor HP akan digunakan sebagai ID login akun kamu.</div>
              </div>

              <div class="col-12 col-md-6">
                <label for="password" class="form-label fw-semibold">Password</label>
                <input type="password" class="form-control rounded-3" id="password" name="password" placeholder="Minimal 6 karakter" required>
              </div>

              <div class="col-12 col-md-6">
                <label for="password_confirmation" class="form-label fw-semibold">Konfirmasi Password</label>
                <input type="password" class="form-control rounded-3" id="password_confirmation" name="password_confirmation" placeholder="Ulangi password" required>
              </div>
            </div>

            <hr class="my-4">
            <label for="referral_code" class="form-label fw-semibold">Kode Referral (Opsional)</label>
            <input id="referral_code" name="referral_code" class="form-control mb-4" maxlength="32" value="{{ old('referral_code', is_string(request()->query('ref')) ? request()->query('ref') : '') }}" placeholder="Kode partner yang merekomendasikan kamu">

            <h5 class="fw-bold mb-3">Pilih Paket Layanan</h5>

            <div class="row g-3 mb-4">
              @foreach($packages as $pkg)
                <div class="col-12 col-md-6">
                  <label class="card h-100 border p-3 rounded-4 cursor-pointer position-relative card-hover">
                    <div class="d-flex align-items-start gap-2">
                      <input type="radio" name="package_id" value="{{ $pkg->id }}" class="form-check-input mt-1"
                        {{ (old('package_id', $selectedPackageId) == $pkg->id || $loop->first) ? 'checked' : '' }} required>
                      <div>
                        <div class="fw-bold text-body fs-6">
                          {{ $pkg->name }}
                          @if($pkg->is_recommended)
                            <span class="badge bg-primary rounded-pill small ms-1">Rekomendasi</span>
                          @endif
                        </div>
                        <div class="text-primary fw-extrabold fs-5 my-1">
                          Rp {{ number_format($pkg->price, 0, ',', '.') }}
                          <span class="fs-6 text-secondary font-normal">/ {{ $pkg->price_label ?: 'layanan' }}</span>
                        </div>
                        <p class="text-secondary small mb-2">{{ $pkg->description }}</p>

                        @if($pkg->features)
                          <ul class="list-unstyled small mb-0 text-secondary">
                            @foreach($pkg->features as $feat)
                              <li>✓ {{ $feat }}</li>
                            @endforeach
                          </ul>
                        @endif
                      </div>
                    </div>
                  </label>
                </div>
              @endforeach
            </div>

            <div class="border rounded-3 p-3 mb-4 bg-body-tertiary">
              <p class="small mb-3">Sebelum mendaftar, baca <a href="{{ route('terms') }}" target="_blank" rel="noopener">Syarat dan Ketentuan Layanan Nugasin Class (buka di tab baru)</a>.</p>
              <input type="hidden" name="terms_version" value="{{ \App\Support\ServiceTerms::VERSION }}">
              <div class="form-check">
                <input class="form-check-input @error('terms_accepted') is-invalid @enderror" type="checkbox" id="terms_accepted" name="terms_accepted" value="1" required>
                <label class="form-check-label" for="terms_accepted">Saya telah membaca, memahami, dan menyetujui Syarat dan Ketentuan Layanan Nugasin Class.</label>
                @error('terms_accepted')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold">Lanjut Pembayaran →</button>
          </form>

          <div class="text-center mt-4 text-secondary">
            Sudah punya akun? <a href="{{ route('login') }}" class="text-primary fw-semibold">Masuk Sekarang</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection
