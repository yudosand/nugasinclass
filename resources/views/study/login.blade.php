@extends('layouts.app')

@section('title', 'Masuk Study Tracker - Nugasin Class')

@section('content')
<main class="container py-5" style="max-width:560px">
  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4 p-lg-5">
      <h1 class="h3 fw-bold mb-2">Masuk Study Tracker</h1>
      <p class="text-secondary mb-4">Gunakan nomor HP atau email yang kamu daftarkan.</p>
      @include('partials.form-errors')
      <form method="POST" action="{{ route('study.login') }}" class="vstack gap-3">
        @csrf
        <div><label class="form-label fw-semibold">Nomor HP / Email</label><input name="identity" class="form-control rounded-3" value="{{ old('identity') }}" required maxlength="255"></div>
        <div><label class="form-label fw-semibold">Password</label><input name="password" type="password" class="form-control rounded-3" required autocomplete="current-password"></div>
        <label class="form-check"><input class="form-check-input" type="checkbox" name="remember" value="1"> <span class="form-check-label">Ingat saya</span></label>
        <div><button class="btn btn-primary rounded-pill px-4">Masuk Tracker</button><a class="btn btn-link" href="{{ route('study.register') }}">Daftar baru</a></div>
      </form>
      <p class="mt-3 mb-0"><a href="{{ route('password.request') }}">Lupa password?</a></p>
    </div>
  </div>
</main>
@endsection
