@extends('layouts.admin')
@section('title', 'Chat Admin - Nugasin Class')
@section('content')
<div class="mb-4">
  <h1 class="h3 fw-bold mb-1">Chat Admin</h1>
  <p class="text-secondary mb-0">Ruang bersama untuk koordinasi tim. Hanya admin yang dapat membaca dan mengirim pesan.</p>
</div>
@if(!$ready)
<div class="card border-0 shadow-sm rounded-4" style="max-width:760px"><div class="card-body p-4">
  <h2 class="h5 fw-bold">Mulai percakapan tim</h2>
  <p>Aktifkan ruang chat sekali agar seluruh admin dapat berkirim pesan dan melihat riwayat percakapan.</p>
  <form method="POST" action="{{ route('admin.chat.activate') }}">@csrf
    <button class="btn btn-primary rounded-pill px-4">Aktifkan Chat Admin</button>
  </form>
</div></div>
@else
<div id="admin-chat" class="card border-0 shadow-sm rounded-4" style="max-width:1100px"
     data-messages-url="{{ route('admin.chat.messages') }}" data-send-url="{{ route('admin.chat.store') }}" data-csrf="{{ csrf_token() }}">
  <div class="card-header bg-transparent p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div><strong>💬 Ruang Tim Nugasin</strong><div class="small text-secondary" id="chat-status" role="status">Memuat percakapan…</div></div>
    <button id="chat-refresh" type="button" class="btn btn-sm btn-outline-primary rounded-pill">Perbarui</button>
  </div>
  <div class="text-center pt-3"><button id="chat-older" type="button" class="btn btn-sm btn-outline-secondary rounded-pill" hidden>Muat pesan sebelumnya</button></div>
  <div id="chat-history" class="p-3 p-lg-4" style="height:clamp(280px,48vh,580px);overflow-y:auto;overflow-wrap:anywhere" aria-label="Riwayat Chat Admin" tabindex="0">
    <p id="chat-empty" class="text-center text-secondary py-5">Memuat pesan…</p>
    <div id="chat-messages"></div>
  </div>
  <div class="text-center pb-2"><button id="chat-new" type="button" class="btn btn-sm btn-primary rounded-pill" hidden>Lihat pesan terbaru ↓</button></div>
  <div class="card-footer bg-transparent p-3 p-lg-4">
    <div id="chat-error" class="alert alert-danger py-2 small" role="alert" hidden></div>
    <form id="chat-form">
      <label class="form-label fw-semibold" for="chat-body">Pesan untuk tim</label>
      <textarea id="chat-body" class="form-control mb-2" rows="3" maxlength="2000" placeholder="Tulis pesan untuk admin lain…" required></textarea>
      <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center">
        <span class="small text-secondary">Maksimal 2.000 karakter. Riwayat tersimpan untuk semua admin.</span>
        <button id="chat-send" class="btn btn-primary rounded-pill px-4" type="submit" disabled>Kirim Pesan</button>
      </div>
    </form>
    <noscript><p class="text-danger mt-2">Aktifkan JavaScript di browser untuk menggunakan Chat Admin.</p></noscript>
  </div>
</div>
@endif
@endsection
@push('scripts')
@if($ready)<script src="{{ asset('assets/js/admin-chat.js') }}" defer></script>@endif
@endpush
