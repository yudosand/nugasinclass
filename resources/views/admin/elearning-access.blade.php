@extends('layouts.secure')
@section('title', 'Buka Akses E-Learning - Nugasin Class')
@section('content')
<main class="container py-5"><div class="row justify-content-center"><div class="col-12 col-lg-7">
  <a href="{{ route('admin.clients.show', $client->id) }}" class="btn btn-outline-secondary rounded-pill mb-3">← Detail Client</a>
  <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4 p-md-5">
    <h1 class="h3 fw-bold">Akses E-Learning</h1><p>Client: <strong>{{ $client->name }}</strong></p>
    @if($hasAccess)
    <p class="text-secondary">Verifikasi password admin Anda untuk membuka akses. Setiap pembukaan dicatat dan kolom akses dikosongkan setelah 45 detik atau ketika tab ditinggalkan.</p>
    <div id="access-error" class="alert alert-danger" role="alert" hidden></div>
    <form id="access-form" action="{{ route('admin.elearning.reveal', $client->id) }}" method="POST" autocomplete="off">
      @csrf
      <label class="form-label fw-semibold" for="current_password">Password admin Anda</label>
      <input class="form-control mb-3" id="current_password" name="current_password" type="password" autocomplete="off" required>
      <button class="btn btn-primary rounded-pill" id="access-open">Verifikasi & Buka Akses</button>
    </form>
    <section id="access-result" class="mt-4" hidden>
      <label class="form-label fw-semibold" for="access-login">NIM / Email E-Learning</label>
      <input id="access-login" class="form-control mb-3" readonly autocomplete="off">
      <label class="form-label fw-semibold" for="access-password">Password E-Learning</label>
      <input id="access-password" class="form-control mb-3" type="password" readonly autocomplete="off">
      <div class="d-flex flex-wrap gap-2"><button class="btn btn-outline-secondary rounded-pill" id="access-toggle" type="button">Tampilkan password</button><button class="btn btn-outline-danger rounded-pill" id="access-hide" type="button">Tutup Akses</button></div>
      <p class="small text-secondary mt-3">Gunakan akses hanya untuk layanan yang disetujui client. Jangan bagikan di Chat Admin atau tangkapan layar.</p>
    </section>
    <hr class="my-4">
    <details id="access-edit"><summary class="fw-bold text-primary">Perbaiki Password E-Learning</summary>
      <p class="text-secondary mt-3">Masukkan password yang benar dari client. Ini memperbarui catatan akses di Nugasin; tidak mengganti password pada situs kampus. NIM/email tetap.</p>
      <div id="access-update-message" class="alert" role="status" hidden></div>
      <form id="access-update-form" action="{{ route('admin.elearning.update', $client->id) }}" method="POST" autocomplete="off">
        @csrf
        <input type="hidden" name="revision" value="{{ $revision }}">
        <label class="form-label fw-semibold" for="access-new-password">Password e-learning yang benar</label>
        <input id="access-new-password" name="elearning_password" type="password" autocomplete="new-password" maxlength="1024" class="form-control mb-3" required>
        <label class="form-label fw-semibold" for="access-confirm-password">Ulangi password e-learning</label>
        <input id="access-confirm-password" name="elearning_password_confirmation" type="password" autocomplete="new-password" maxlength="1024" class="form-control mb-3" required>
        <label class="form-label fw-semibold" for="access-edit-admin-password">Password admin Anda</label>
        <input id="access-edit-admin-password" name="current_password" type="password" autocomplete="off" class="form-control mb-3" required>
        <div class="form-check mb-3"><input id="access-edit-confirmed" name="confirmed" type="checkbox" value="1" class="form-check-input" required><label class="form-check-label" for="access-edit-confirmed">Password ini sudah dikonfirmasi oleh client untuk memperbaiki akses yang tersimpan.</label></div>
        <button id="access-update-button" class="btn btn-primary rounded-pill">Simpan Perbaikan Password</button>
      </form>
    </details>
    @if($changes->isNotEmpty())<h2 class="h6 fw-bold mt-4">Perubahan Password Terakhir</h2><ul class="small text-secondary">@foreach($changes as $change)<li>{{ \App\Support\Cuanin::date($change->created_at) }} · {{ $change->name ?? 'Admin' }} memperbarui password tersimpan.</li>@endforeach</ul>@endif
    @else<p class="alert alert-warning">Client belum mengirim akses e-learning.</p>@endif
  </div></div>
</div></div></main>
@endsection
@push('scripts')
@if($hasAccess)<script src="{{ asset('assets/js/elearning-access.js') }}?v=20261007" defer></script>@endif
@endpush
