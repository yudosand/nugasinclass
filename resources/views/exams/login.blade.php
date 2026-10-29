@extends('layouts.app')
@section('title','Masuk Simulasi UAS - Nugasin Class')
@section('content')
<main class="container py-5"><div class="row justify-content-center"><div class="col-lg-5"><div class="card rounded-4 shadow-sm border-0"><div class="card-body p-4">
<h1 class="h3 fw-bold">Masuk Simulasi UAS</h1>@include('partials.form-errors')
<form method="POST" action="{{ route('exams.login') }}">@csrf
<label for="nomor_hp" class="form-label">Nomor WhatsApp / HP</label><input id="nomor_hp" name="nomor_hp" class="form-control mb-3" value="{{ old('nomor_hp') }}" autocomplete="username" required>
<label for="password" class="form-label">Password</label><input id="password" type="password" name="password" class="form-control mb-3" autocomplete="current-password" required>
<button class="btn btn-primary rounded-pill w-100">Masuk Simulasi</button></form><div class="d-flex justify-content-between mt-3"><a href="{{ route('password.request') }}">Lupa password?</a><a href="{{ route('exams.register') }}">Daftar akun</a></div>
</div></div></div></div></main>
@endsection
