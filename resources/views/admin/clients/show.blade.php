@extends('layouts.admin')

@section('title', 'Detail Client - Admin Backoffice')

@section('content')
<div class="mb-4">
  <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill mb-2">← Kembali ke Daftar Client</a>
  <h2 class="fw-bold mb-1">Detail Client: {{ $client->name }}</h2>
  <p class="text-secondary">Informasi akun, data akademik, order, dan status pengerjaan.</p>
</div>

@include('partials.form-errors')
<div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4">
  <h5 class="fw-bold">Akses E-Learning</h5>
  @if(!$accessReady)
    <p>Aktifkan penyimpanan akses sekali agar client dapat mengisi kredensial saat onboarding.</p>
    <form method="POST" action="{{ route('admin.elearning.activate') }}">@csrf<button class="btn btn-primary rounded-pill">Aktifkan Penyimpanan Akses E-Learning</button></form>
  @elseif($hasAccess)
    <p class="text-secondary">Akses sudah tersimpan. NIM/email dan password hanya dikirim setelah verifikasi password admin.</p>
    <a class="btn btn-outline-primary rounded-pill" href="{{ route('admin.elearning.show', $client->id) }}">Buka Akses E-Learning</a>
  @else
    <p class="text-secondary mb-0">Client belum mengirim akses e-learning. Client dapat melengkapinya melalui menu Edit Data Akademik di dashboard.</p>
  @endif
</div></div>
@if(session('reset_link'))
  <div class="alert alert-success mb-4">
    <h5 class="fw-bold">Tautan Reset Password Client</h5>
    <p class="mb-2">Salin tautan ini dan kirim secara pribadi kepada pemilik akun yang sudah diverifikasi. Berlaku {{ config('auth.passwords.users.expire', 60) }} menit, sekali pakai. Tautan sebelumnya tidak berlaku.</p>
    <label class="form-label" for="reset_link">Tautan reset untuk {{ $client->name }}</label>
    <input class="form-control" id="reset_link" readonly value="{{ session('reset_link') }}" onclick="this.select()">
    <div class="small mt-2">Klik kolom untuk memilih tautan, lalu salin. Tautan hanya ditampilkan setelah dibuat.</div>
  </div>
@endif
<div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4">
  <div class="d-flex flex-wrap gap-3 justify-content-between align-items-start">
    <details class="flex-grow-1"><summary class="fw-bold text-primary">Bantu Client Lupa Password</summary>
      <p class="text-secondary mt-3">Verifikasi kepemilikan akun melalui nomor terdaftar dan data pesanan sebelum membuat tautan. Jangan meminta password lama atau OTP client.</p>
      <form method="POST" action="{{ route('admin.clients.reset-password', $client->id) }}">@csrf
        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="identity_verified" id="identity_verified" value="1" required><label class="form-check-label" for="identity_verified">Saya sudah memverifikasi identitas pemilik akun ini.</label></div>
        <label class="form-label" for="reset_current_password">Password admin Anda</label><input class="form-control mb-3" style="max-width:420px" id="reset_current_password" name="current_password" type="password" autocomplete="current-password" required>
        <button class="btn btn-outline-primary rounded-pill" type="submit">Buat Tautan Reset Password</button>
      </form>
    </details>
    <a href="{{ route('admin.clients.delete', $client->id) }}" class="btn btn-outline-danger rounded-pill">Hapus Client</a>
  </div>
</div></div>
<div class="row g-4 mb-4">
  <!-- Info Akun -->
  <div class="col-12 col-md-6">
    <div class="card border-0 shadow-sm rounded-4 bg-body h-100">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-3">Informasi Akun</h5>
        <div class="border rounded-3 p-3 mb-3">
          <div class="fw-semibold">Persetujuan Syarat &amp; Ketentuan</div>
          @if($termsAcceptance)
            <div class="text-success">Disetujui saat pendaftaran</div>
            <div class="small text-secondary">Versi {{ $termsAcceptance->version }} · {{ \Illuminate\Support\Carbon::parse($termsAcceptance->accepted_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</div>
          @else
            <div class="small text-secondary">Belum ada catatan persetujuan S&amp;K layanan untuk akun ini.</div>
          @endif
        </div>
        <table class="table table-borderless">
          <tr>
            <td class="text-secondary ps-0">Nama:</td>
            <td class="fw-bold">{{ $client->name }}</td>
          </tr>
          <tr>
            <td class="text-secondary ps-0">Nomor HP:</td>
            <td class="fw-bold">{{ $client->nomor_hp }}</td>
          </tr>
          <tr>
            <td class="text-secondary ps-0">Email:</td>
            <td>{{ $client->email ?: '-' }}</td>
          </tr>
          <tr>
            <td class="text-secondary ps-0">Status Akun:</td>
            <td>
              @if($client->status === 'approved')
                <span class="badge bg-success rounded-pill">Approved</span>
              @elseif($client->status === 'pending_approval')
                <span class="badge bg-warning text-dark rounded-pill">Perlu Verifikasi Pembayaran</span>
              @elseif($client->status === 'pending_payment')
                <span class="badge bg-secondary rounded-pill">Pending Payment</span>
              @elseif($client->status === 'rejected')
                <span class="badge bg-danger rounded-pill">Rejected</span>
              @endif
            </td>
          </tr>
          <tr>
            <td class="text-secondary ps-0">Di-handle oleh:</td>
            <td>
              @if($client->handlerAdmin)
                <span class="badge {{ (int) $client->handler_admin_id === (int) auth()->id() ? 'bg-success' : 'bg-primary-subtle text-primary' }} rounded-pill">
                  {{ (int) $client->handler_admin_id === (int) auth()->id() ? 'Saya' : $client->handlerAdmin->name }}
                </span>
              @else
                <span class="text-secondary">Belum diambil admin</span>
              @endif
            </td>
          </tr>
          <tr>
            <td class="text-secondary ps-0">Tanggal Daftar:</td>
            <td>{{ $client->created_at->format('d F Y, H:i') }} WIB</td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <!-- Profil Akademik -->
  <div class="col-12 col-md-6">
    <div class="card border-0 shadow-sm rounded-4 bg-body h-100">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-3">Profil Akademik (Onboarding)</h5>
        @if($client->profile && $client->profile->is_onboarding_completed)
          <table class="table table-borderless">
            <tr>
              <td class="text-secondary ps-0">Universitas:</td>
              <td class="fw-bold">{{ $client->profile->universitas }}</td>
            </tr>
            <tr>
              <td class="text-secondary ps-0">NIM:</td>
              <td class="fw-bold">{{ $client->profile->nim }}</td>
            </tr>
            <tr>
              <td class="text-secondary ps-0">Program Studi:</td>
              <td class="fw-bold">{{ $client->profile->program_studi }}</td>
            </tr>
            <tr>
              <td class="text-secondary ps-0">Semester:</td>
              <td class="fw-bold">Semester {{ $client->profile->semester }}</td>
            </tr>
            <tr>
              <td class="text-secondary ps-0">Persetujuan S&K:</td>
              <td class="text-success fw-bold">✓ Disetujui ({{ $client->profile->agreed_terms_at ? $client->profile->agreed_terms_at->format('d M Y, H:i') : '-' }})</td>
            </tr>
          </table>
        @else
          <div class="alert alert-warning rounded-3 mb-0">
            Client belum melengkapi profil akademik onboarding.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm rounded-4 bg-body mb-4">
  <div class="card-body p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold mb-0">Riwayat Order & Pembayaran</h5>
    </div>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr class="text-secondary small">
            <th>Order #</th>
            <th>Paket</th>
            <th>Total</th>
            <th>Bukti Bayar</th>
            <th>Status Order</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($client->orders as $ord)
            <tr>
              <td class="fw-bold">{{ $ord->order_number }}</td>
              <td>{{ $ord->package->name }}</td>
              <td class="fw-bold">Rp {{ number_format($ord->amount, 0, ',', '.') }}</td>
              <td>
                @if($ord->payment_proof)
                  <a href="{{ route('payment.proof', $ord->id) }}" target="_blank" class="btn btn-sm btn-outline-info rounded-pill">📷 Lihat Bukti</a>
                @else
                  <span class="text-secondary">Belum Upload</span>
                @endif
              </td>
              <td>
                @if($ord->received_amount !== null)<span class="badge bg-primary">{{ $ord->payment_label }}</span><div class="small">Sisa {{ \App\Support\Cuanin::format($ord->outstanding_amount) }}</div>
                @elseif($ord->payment_status === 'approved')
                  <span class="badge bg-success rounded-pill">Approved</span>
                @elseif($ord->payment_status === 'paid')
                  <span class="badge bg-warning text-dark rounded-pill">Waiting Verification</span>
                @elseif($ord->payment_status === 'rejected')
                  <span class="badge bg-danger rounded-pill">Rejected</span>
                @else
                  <span class="badge bg-secondary rounded-pill">Pending Payment</span>
                @endif
              </td>
              <td>
                <a href="{{ route('admin.orders.show', $ord->id) }}" class="btn btn-sm btn-outline-primary rounded-pill">Detail Order</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-secondary py-3">Belum ada order.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm rounded-4 bg-body">
  <div class="card-body p-4 d-flex justify-content-between align-items-center">
    <div>
      <h5 class="fw-bold mb-1">Progress Akademik Client (Sesi 1-8)</h5>
      <p class="text-secondary mb-0">Kelola mata kuliah, progress diskusi, tugas, dan nilai client ini.</p>
    </div>
    <a href="{{ route('admin.academic.show', $client->id) }}" class="btn btn-primary rounded-pill px-4 fw-bold">Kelola Academic Progress →</a>
  </div>
</div>
@endsection
