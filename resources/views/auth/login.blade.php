@extends('layouts.app')

@section('title', 'Login Client & Admin - Nugasin Class')

@section('content')
<main class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 p-md-5">
          <div class="text-center mb-4">
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-2">Masuk Akun</span>
            <h1 class="h3 fw-bold mb-1">Selamat Datang Kembali</h1>
            <p class="text-secondary">Masukkan Nomor HP & Password terdaftar.</p>
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

          <form action="{{ route('login') }}" method="POST">
            @csrf

            <div class="mb-3">
              <label for="nomor_hp" class="form-label fw-semibold">Nomor WhatsApp / HP</label>
              <div class="input-group">
                <span class="input-group-text">📱</span>
                <input type="text" class="form-control rounded-3" id="nomor_hp" name="nomor_hp" value="{{ old('nomor_hp') }}" placeholder="Contoh: 08123456789" required autofocus>
              </div>
            </div>

            <div class="mb-4">
              <label for="password" class="form-label fw-semibold">Password</label>
              <input type="password" class="form-control rounded-3" id="password" name="password" placeholder="Masukkan password" required>
            </div>

            <div class="mb-4 d-flex justify-content-between align-items-center">
              <div class="form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label text-secondary" for="remember">Ingat Saya</label>
              </div>
              <a href="{{ route('password.request') }}" class="small">Lupa password?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold">Masuk Sekarang</button>
          </form>

          <div class="text-center mt-4 text-secondary">
            Belum punya akun? <a href="{{ route('register') }}" class="text-primary fw-semibold">Daftar Paket Baru</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection
