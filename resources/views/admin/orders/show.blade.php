@extends('layouts.admin')

@section('title', 'Verifikasi Pembayaran - Admin Backoffice')

@section('content')
<div class="mb-4">
  <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill mb-2">← Kembali ke Orders</a>
  <h2 class="fw-bold mb-1">Verifikasi Pembayaran: Order #{{ $order->order_number }}</h2>
  <p class="text-secondary">Verifikasi pembayaran sesuai nominal yang benar-benar diterima.</p>
</div>

<div class="row g-4">
  <div class="col-12">@include('partials.form-errors')@include('admin.orders.test-order')@if(!$order->is_test_order)@if($cashReady)@include('admin.cash.order')@else<div class="alert alert-info"><a href="{{ route('admin.cash.setup') }}">Cocokkan kas awal</a> untuk mulai mencatat pembayaran sesuai nominal nyata.</div>@include('admin.orders.distribution')@endif @endif</div>
  <!-- Detail Order & Client -->
  <div class="col-12 col-lg-6">
    <div class="card border-0 shadow-sm rounded-4 bg-body mb-4">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-3">Rincian Order & Client</h5>
        <table class="table table-borderless mb-0">
          <tr>
            <td class="text-secondary ps-0">Order Number:</td>
            <td class="fw-bold">{{ $order->order_number }}</td>
          </tr>
          <tr>
            <td class="text-secondary ps-0">Nama Client:</td>
            <td class="fw-bold">{{ $order->user->name }}</td>
          </tr>
          <tr>
            <td class="text-secondary ps-0">Nomor HP Client:</td>
            <td class="fw-bold">{{ $order->user->nomor_hp }}</td>
          </tr>
          <tr>
            <td class="text-secondary ps-0">Paket Layanan:</td>
            <td><span class="badge bg-primary-subtle text-primary rounded-pill">{{ $order->package->name }}</span></td>
          </tr>
          <tr>
            <td class="text-secondary ps-0">Total Nominal:</td>
            <td class="fw-bold fs-5 text-primary">Rp {{ number_format($order->amount, 0, ',', '.') }}</td>
          </tr>
          <tr>
            <td class="text-secondary ps-0">Status Pembayaran:</td>
            <td>
              @if($cashReady)<span class="badge bg-primary">{{ $order->payment_label }}</span>
              @elseif($order->payment_status === 'approved')
                <span class="badge bg-success rounded-pill">Approved</span>
              @elseif($order->payment_status === 'paid')
                <span class="badge bg-warning text-dark rounded-pill">Perlu Verification</span>
              @elseif($order->payment_status === 'rejected')
                <span class="badge bg-danger rounded-pill">Rejected</span>
              @else
                <span class="badge bg-secondary rounded-pill">Pending Payment</span>
              @endif
            </td>
          </tr>
          @if($order->notes)
            <tr>
              <td class="text-secondary ps-0">Catatan Admin:</td>
              <td class="text-body fst-italic">{{ $order->notes }}</td>
            </tr>
          @endif
        </table>
      </div>
    </div>

    <!-- Action Buttons -->
    @if(!$order->is_test_order)
    <div class="card border-0 shadow-sm rounded-4 bg-body">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-3">Aksi Admin</h5>

        <div class="d-flex flex-column gap-3">
          <!-- Approve Form -->
          <p class="text-secondary">{{ $cashReady ? 'Gunakan form Catat Pembayaran Diterima di atas untuk pembayaran baru atau pelunasan.' : 'Selesaikan pencocokan kas awal sebelum menyetujui pembayaran berikutnya.' }}</p>

          <hr class="my-2">

          <!-- Reject Form -->
          <button type="button" class="btn btn-outline-danger rounded-pill" data-bs-toggle="collapse" data-bs-target="#rejectFormCollapse">
            ✕ Reject / Tolak Pembayaran
          </button>

          <div class="collapse" id="rejectFormCollapse">
            <div class="p-3 border rounded-3 bg-body-tertiary">
              <form action="{{ route('admin.orders.reject', $order->id) }}" method="POST">
                @csrf
                <div class="mb-3">
                  <label for="rejection_reason" class="form-label fw-semibold text-danger">Alasan Penolakan</label>
                  <textarea name="rejection_reason" id="rejection_reason" rows="3" class="form-control rounded-3" placeholder="Masukkan alasan penolakan pembayaran..." required></textarea>
                </div>
                <button type="submit" class="btn btn-danger w-100 rounded-pill fw-bold" onclick="return confirm('Apakah Anda yakin ingin MENOLAK pembayaran ini?')">
                  Konfirmasi Reject
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    @endif
  </div>

  <!-- Preview Bukti Pembayaran -->
  <div class="col-12 col-lg-6">
    <div class="card border-0 shadow-sm rounded-4 bg-body h-100">
      <div class="card-body p-4 text-center">
        <h5 class="fw-bold mb-3 text-start">Bukti Transfer yang Di-upload</h5>

        @if($order->payment_proof)
          @if(Str::endsWith(strtolower($order->payment_proof), '.pdf'))
            <div class="p-5 border rounded-4 bg-body-tertiary">
              <div class="fs-1 mb-2">📄</div>
              <h6 class="fw-bold">Dokumen Bukti Transfer PDF</h6>
              <a href="{{ route('payment.proof', $order->id) }}" target="_blank" class="btn btn-primary rounded-pill px-4 mt-2">Buka Dokumen PDF ↗</a>
            </div>
          @else
            <div class="border rounded-4 p-2 bg-body-tertiary" style="max-height: 550px; overflow: auto;">
              <img src="{{ route('payment.proof', $order->id) }}" alt="Bukti Transfer" class="img-fluid rounded-3" style="max-width: 100%;">
            </div>
            <a href="{{ route('payment.proof', $order->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill mt-3">🔍 Zoom / Open Original Image</a>
          @endif
        @else
          <div class="p-5 text-secondary border rounded-4 bg-body-tertiary">
            <div class="fs-1 mb-2">📷</div>
            Client belum meng-upload bukti pembayaran.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
