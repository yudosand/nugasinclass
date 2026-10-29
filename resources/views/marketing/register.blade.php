@extends('layouts.app')
@section('title', 'Daftar Marketing - Nugasin Partner')
@section('content')
<main class="container py-5"><div class="row justify-content-center"><div class="col-12 col-md-8 col-lg-6"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4 p-md-5">
<h1 class="h3 fw-bold">Daftar sebagai Marketing</h1><p class="text-secondary">Buat akun Nugasin Partner dan kode referral milikmu.</p>
@include('partials.form-errors')
@if(!$ready)<div class="alert alert-info">Pendaftaran marketing akan tersedia setelah admin mengaktifkan Cuanin.</div>@endif
<form method="POST" action="{{ route('marketing.register') }}">@csrf
<label for="name" class="form-label">Nama</label><input id="name" name="name" value="{{ old('name') }}" class="form-control mb-3" maxlength="255" required autocomplete="name">
<label for="email" class="form-label">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control mb-3" maxlength="255" required autocomplete="email">
<label for="referral_code" class="form-label">Kode Referral Kamu</label><input id="referral_code" name="referral_code" value="{{ old('referral_code') }}" class="form-control" minlength="4" maxlength="24" pattern="[A-Za-z0-9][A-Za-z0-9_-]{3,23}" required placeholder="Contoh: RANI2026"><div class="form-text mb-3">4–24 karakter: huruf, angka, tanda - atau _. Kode harus unik dan digunakan client saat mendaftar.</div>
<label for="password" class="form-label">Password</label><input id="password" name="password" type="password" class="form-control mb-4" minlength="8" maxlength="72" required autocomplete="new-password" placeholder="Minimal 8 karakter">
<button class="btn btn-primary rounded-pill w-100" @disabled(!$ready)>Daftar sebagai Marketing</button>
</form><p class="text-center mt-4 mb-0">Sudah punya akun? <a href="{{ route('marketing.login') }}">Login Marketing</a></p>
</div></div></div></div></main>
@endsection
