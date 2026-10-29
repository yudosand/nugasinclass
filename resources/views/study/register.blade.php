@extends('layouts.app')

@section('title', 'Daftar Study Tracker - Nugasin Class')

@section('content')
<main class="container py-5" style="max-width:720px">
  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4 p-lg-5">
      <h1 class="h3 fw-bold mb-2">Daftar Study Tracker</h1>
      <p class="text-secondary mb-4">Buat akun tracker untuk mencatat progres e-learning kamu.</p>
      @include('partials.form-errors')
      <form method="POST" action="{{ route('study.register') }}" class="row g-3">
        @csrf
        <div class="col-12"><label class="form-label fw-semibold">Nama</label><input name="name" class="form-control rounded-3" value="{{ old('name') }}" required maxlength="255"></div>
        <div class="col-12 col-md-6"><label class="form-label fw-semibold">Nomor HP</label><input name="nomor_hp" class="form-control rounded-3" value="{{ old('nomor_hp') }}" required inputmode="numeric" maxlength="15"></div>
        <div class="col-12 col-md-6"><label class="form-label fw-semibold">Email opsional</label><input name="email" type="email" class="form-control rounded-3" value="{{ old('email') }}" maxlength="255"></div>
        <div class="col-12 col-md-6"><label class="form-label fw-semibold">Password</label><input name="password" type="password" class="form-control rounded-3" required minlength="8" autocomplete="new-password"></div>
        <div class="col-12 col-md-6"><label class="form-label fw-semibold">Konfirmasi Password</label><input name="password_confirmation" type="password" class="form-control rounded-3" required minlength="8" autocomplete="new-password"></div>
        <div class="col-12"><button class="btn btn-primary rounded-pill px-4">Buat Akun Tracker</button><a class="btn btn-link" href="{{ route('study.login') }}">Sudah punya akun?</a></div>
      </form>
    </div>
  </div>
</main>
@endsection
