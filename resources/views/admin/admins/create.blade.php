@extends('layouts.admin')
@section('title', 'Tambah Admin - Nugasin Class')
@section('content')
<a href="{{ route('admin.admins.index') }}" class="btn btn-outline-secondary rounded-pill mb-3">← Daftar Admin</a>
<div class="card border-0 shadow-sm rounded-4" style="max-width: 760px"><div class="card-body p-4">
  <h1 class="h3 fw-bold">Tambah Admin Baru</h1><p class="text-secondary">Admin baru memiliki akses penuh ke backoffice, termasuk data client dan pembayaran.</p>
  @include('partials.form-errors')
  <form method="POST" action="{{ route('admin.admins.store') }}">@csrf
    <label class="form-label" for="name">Nama admin</label><input class="form-control mb-3" id="name" name="name" value="{{ old('name') }}" maxlength="255" required>
    <label class="form-label" for="nomor_hp">Nomor HP untuk login</label><input class="form-control mb-3" id="nomor_hp" name="nomor_hp" value="{{ old('nomor_hp') }}" inputmode="tel" pattern="[0-9]{9,15}" maxlength="15" placeholder="08xxxxxxxxxx" required>
    <label class="form-label" for="password">Password admin baru (minimal 8 karakter)</label><input class="form-control mb-3" id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
    <label class="form-label" for="password_confirmation">Ulangi password admin baru</label><input class="form-control mb-3" id="password_confirmation" name="password_confirmation" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
    <hr><label class="form-label" for="current_password">Password Anda untuk mengonfirmasi</label><input class="form-control mb-4" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
    <button class="btn btn-primary rounded-pill px-4" type="submit">Tambahkan Admin</button>
  </form>
</div></div>
@endsection
