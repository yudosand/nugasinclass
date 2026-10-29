@extends('layouts.app')
@section('title','Daftar Simulasi UAS - Nugasin Class')
@section('content')
<main class="container py-5"><div class="row justify-content-center"><div class="col-lg-7"><div class="card rounded-4 shadow-sm border-0"><div class="card-body p-4 p-md-5">
<h1 class="h3 fw-bold">Daftar Simulasi UAS</h1><p class="text-secondary">Buat akun, pilih paket soal, lalu bayar dan tunggu persetujuan admin.</p>
@include('partials.form-errors')
<form method="POST" action="{{ route('exams.register') }}">@csrf
@foreach(['name'=>'Nama lengkap','nomor_hp'=>'Nomor WhatsApp / HP','email'=>'Email (opsional)'] as $field=>$label)<div class="mb-3"><label class="form-label" for="{{ $field }}">{{ $label }}</label><input class="form-control" id="{{ $field }}" name="{{ $field }}" type="{{ $field==='email'?'email':'text' }}" value="{{ old($field) }}" maxlength="{{ $field==='nomor_hp'?15:255 }}" @required($field!=='email')></div>@endforeach
<div class="mb-3"><label for="password" class="form-label">Password (minimal 8 karakter)</label><input class="form-control" type="password" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required></div>
<div class="mb-3"><label for="password_confirmation" class="form-label">Ulangi password</label><input class="form-control" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required></div>
<input type="hidden" name="terms_version" value="{{ \App\Support\ServiceTerms::VERSION }}"><div class="form-check mb-3"><input class="form-check-input" id="terms_accepted" name="terms_accepted" type="checkbox" value="1" required><label class="form-check-label" for="terms_accepted">Saya telah membaca dan menyetujui <a href="{{ route('terms') }}" target="_blank" rel="noopener">Syarat & Ketentuan</a>.</label></div>
<button class="btn btn-primary rounded-pill w-100">Daftar & Pilih Paket Soal</button></form><p class="text-center mt-3 mb-0">Sudah punya akun Nugasin? <a href="{{ route('exams.login') }}">Masuk di sini</a>.</p>
</div></div></div></div></main>
@endsection
