@extends('layouts.app')
@section('title', 'Reset Password - Nugasin Class')
@section('content')
<main class="container py-5"><div class="row justify-content-center"><div class="col-12 col-md-8 col-lg-6">
<div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4 p-md-5">
  <h1 class="h3 fw-bold">Buat Password Baru</h1>
  <p class="text-secondary">Buat password baru minimal {{ $minimum }} karakter untuk akun yang sudah diverifikasi admin.</p>
  @include('partials.form-errors')
  <form method="POST" action="{{ route('password.store') }}">@csrf
    <input type="hidden" name="token" value="{{ $token }}">
    @if($accountId)
      <input type="hidden" name="account_id" value="{{ $accountId }}">
    @else
      <label class="form-label" for="nomor_hp">Nomor HP terdaftar</label><input class="form-control mb-3" id="nomor_hp" name="nomor_hp" type="text" inputmode="tel" autocomplete="username" maxlength="30" value="{{ old('nomor_hp', is_string($phone) ? $phone : '') }}" required>
    @endif
    <label class="form-label" for="password">Password baru</label><input class="form-control mb-3" id="password" name="password" type="password" minlength="{{ $minimum }}" maxlength="72" autocomplete="new-password" required>
    <label class="form-label" for="password_confirmation">Ulangi password baru</label><input class="form-control mb-4" id="password_confirmation" name="password_confirmation" type="password" minlength="{{ $minimum }}" maxlength="72" autocomplete="new-password" required>
    <button class="btn btn-primary rounded-pill px-4" type="submit">Reset Password</button>
  </form>
  <p class="mt-3 mb-0"><a href="{{ route('password.request') }}">Tautan tidak berlaku? Hubungi admin</a></p>
</div></div></div></div></main>
@endsection
