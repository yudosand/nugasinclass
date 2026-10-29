@extends('layouts.admin')
@section('title', 'Hapus Marketing - Nugasin Class')
@section('content')
<a class="btn btn-outline-secondary rounded-pill mb-3" href="{{ route('admin.marketing.index') }}">← Daftar Marketing</a>
<div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
  <h1 class="h3 fw-bold">Hapus Marketing</h1>
  <p class="mb-1 fw-semibold">{{ $marketing->name }}</p><p class="text-secondary">{{ $marketing->email }}</p>
  @include('partials.form-errors')
  <div class="alert alert-warning">Akun akan dihapus dari daftar marketing aktif. Akses login dan kode referral dihentikan. Identitas serta riwayat keuangan tetap diarsipkan untuk pembukuan; client referral tidak dihapus. Email dan kode lama tetap dicadangkan agar riwayat tidak tertukar.</div>
  @if($blockers)
    <div class="alert alert-danger"><strong>Akun belum dapat dihapus:</strong><ul class="mb-0 mt-2">@foreach($blockers as $blocker)<li>{{ $blocker }}</li>@endforeach</ul></div>
    <a class="btn btn-outline-primary rounded-pill" href="{{ route('cuanin.index') }}">Buka Cuanin</a>
  @else
    <form method="POST" action="{{ route('admin.marketing.destroy', $marketing->id) }}">
      @csrf @method('DELETE')
      <label class="form-label" for="confirm_email">Ketik email marketing untuk konfirmasi</label>
      <input class="form-control mb-3" id="confirm_email" name="confirm_email" type="email" autocomplete="off" required>
      <label class="form-label" for="current_password">Password admin Anda</label>
      <input class="form-control mb-3" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
      <button class="btn btn-danger rounded-pill">Hapus Marketing</button>
    </form>
  @endif
</div></div>
@endsection
