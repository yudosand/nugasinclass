@extends('layouts.admin')
@section('title', 'Reset Password Akun - Nugasin Class')
@section('content')
<div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
  <h1 class="h3 fw-bold">Reset Password Akun</h1>
  <p><strong>{{ $account->name }}</strong> • {{ ['admin'=>'Admin', 'client'=>'Client', 'marketing'=>'Marketing', 'student'=>'Study Tracker'][$account->role] }}<br>{{ $account->nomor_hp ?: $account->email }}</p>
  @include('partials.form-errors')
  @if(session('account_reset_link'))
    <div class="alert alert-success">
      <label for="account_reset_link" class="form-label fw-bold">Tautan reset password</label>
      <input id="account_reset_link" class="form-control" readonly value="{{ session('account_reset_link') }}">
      <p class="mt-2 mb-0">Salin dan kirim pribadi kepada pemilik akun. Berlaku {{ config('auth.passwords.users.expire', 60) }} menit, sekali pakai. Tautan sebelumnya tidak berlaku.</p>
    </div>
  @endif
  <p class="text-secondary">Verifikasi kepemilikan akun melalui kontak terdaftar. Untuk akun admin, konfirmasikan langsung dengan rekan admin yang bersangkutan. Jangan meminta password lama atau OTP.</p>
  <form method="POST" action="{{ route('admin.accounts.reset.issue', $account->id) }}">
    @csrf
    <div class="form-check mb-3"><input id="identity_verified" class="form-check-input" name="identity_verified" type="checkbox" value="1" required><label for="identity_verified" class="form-check-label">Saya sudah memverifikasi identitas pemilik akun ini.</label></div>
    <label for="current_password" class="form-label">Password admin Anda yang sedang login</label>
    <input id="current_password" class="form-control mb-3" type="password" name="current_password" autocomplete="current-password" required>
    <button class="btn btn-primary rounded-pill">Buat Tautan Reset Password</button>
  </form>
</div></div>
@endsection
