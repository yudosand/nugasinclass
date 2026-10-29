@extends('layouts.admin')
@section('title', 'Profil Saya - Nugasin Class')
@section('content')
<div class="card border-0 shadow-sm rounded-4" style="max-width:760px"><div class="card-body p-4">
  <h1 class="h3 fw-bold">Profil Saya</h1>
  <p class="text-secondary">Perbarui nama yang ditampilkan di backoffice dan Chat Admin.</p>
  @include('partials.form-errors')
  <form method="POST" action="{{ route('admin.profile.update') }}">@csrf @method('PUT')
    <label class="form-label" for="name">Nama Anda</label>
    <input class="form-control mb-3" id="name" name="name" value="{{ old('name', auth()->user()->name) }}" maxlength="255" autocomplete="name" required>
    <p class="small text-secondary">Nomor HP untuk login: {{ auth()->user()->nomor_hp }}</p>
    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Nama</button>
    <a href="{{ route('password.edit') }}" class="btn btn-outline-secondary rounded-pill">Ubah Password</a>
  </form>
</div></div>
@endsection
