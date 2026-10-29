@extends('layouts.admin')
@section('title', 'Hapus Client - Nugasin Class')
@section('content')
<a href="{{ route('admin.clients.show', $client->id) }}" class="btn btn-outline-secondary rounded-pill mb-3">← Kembali ke Client</a>
<div class="card border-danger rounded-4" style="max-width:760px"><div class="card-body p-4">
  <h1 class="h3 fw-bold text-danger">Hapus Client</h1>
  <p><strong>{{ $client->name }}</strong> · {{ $client->nomor_hp }}</p>
  <div class="alert alert-danger">Penghapusan permanen mencakup akun, profil, {{ $client->orders_count }} pesanan beserta bukti pembayaran, {{ $client->courses_count }} mata kuliah, dan semua progres akademik client ini. Data tidak dapat dipulihkan melalui aplikasi.</div>
  @include('partials.form-errors')
  <form method="POST" action="{{ route('admin.clients.destroy', $client->id) }}">@csrf @method('DELETE')
    <label class="form-label" for="confirm_phone">Ketik nomor HP {{ $client->nomor_hp }} untuk konfirmasi</label><input class="form-control mb-3" id="confirm_phone" name="confirm_phone" inputmode="tel" autocomplete="off" required>
    <label class="form-label" for="current_password">Password admin Anda</label><input class="form-control mb-4" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
    <div class="d-flex gap-2"><a class="btn btn-outline-secondary rounded-pill" href="{{ route('admin.clients.show', $client->id) }}">Batal</a><button class="btn btn-danger rounded-pill" type="submit">Hapus Client Permanen</button></div>
  </form>
</div></div>
@endsection
