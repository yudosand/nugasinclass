@extends('layouts.app')
@section('title', 'Login Marketing - Nugasin Partner')
@section('content')
<main class="container py-5"><div class="row justify-content-center"><div class="col-12 col-md-8 col-lg-6"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4 p-md-5">
<h1 class="h3 fw-bold">Login Marketing</h1><p class="text-secondary">Masuk untuk melihat Cuanin dan client referral kamu.</p>@include('partials.form-errors')
<form method="POST" action="{{ route('marketing.login') }}">@csrf
<label for="email" class="form-label">Email</label><input id="email" name="email" value="{{ old('email') }}" type="email" class="form-control mb-3" required autocomplete="username">
<label for="password" class="form-label">Password</label><input id="password" name="password" type="password" class="form-control mb-4" required autocomplete="current-password">
<button class="btn btn-primary rounded-pill w-100">Masuk ke Cuanin</button></form><p class="mt-3"><a href="{{ route('password.request') }}">Lupa password?</a></p><p class="text-center mt-4 mb-0">Belum bergabung? <a href="{{ route('marketing.register') }}">Daftar sebagai Marketing</a></p>
</div></div></div></div></main>
@endsection
