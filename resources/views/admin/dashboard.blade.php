@extends('layouts.admin')

@section('title', 'Admin Dashboard - Nugasin Class')

@section('content')
<div class="mb-4">
  <h2 class="fw-bold mb-1">Dashboard Backoffice</h2>
  <p class="text-secondary">Selamat datang di sistem manajemen Nugasin Class.</p>
</div>

<!-- Metrics Overview Cards -->
<div class="row g-3 mb-4">
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-body">
      <div class="d-flex align-items-center gap-3">
        <div class="fs-2 bg-primary-subtle text-primary p-3 rounded-4">👥</div>
        <div>
          <div class="text-secondary small fw-semibold">Total Client</div>
          <div class="fs-4 fw-bold">{{ number_format($totalClients) }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-lg-3">
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-body">
      <div class="d-flex align-items-center gap-3">
        <div class="fs-2 bg-warning-subtle text-warning p-3 rounded-4">💳</div>
        <div>
          <div class="text-secondary small fw-semibold">Butuh Verification</div>
          <div class="fs-4 fw-bold text-warning">{{ number_format($pendingPayments) }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-lg-3">
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-body">
      <div class="d-flex align-items-center gap-3">
        <div class="fs-2 bg-success-subtle text-success p-3 rounded-4">✓</div>
        <div>
          <div class="text-secondary small fw-semibold">Order Approved</div>
          <div class="fs-4 fw-bold text-success">{{ number_format($approvedOrders) }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-lg-3">
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-body">
      <div class="d-flex align-items-center gap-3">
        <div class="fs-2 bg-info-subtle text-info p-3 rounded-4">🏷️</div>
        <div>
          <div class="text-secondary small fw-semibold">Paket Layanan</div>
          <div class="fs-4 fw-bold">{{ number_format($totalPackages) }}</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Recent Orders Table -->
@include('admin.partials.client-progress')

<div class="card border-0 shadow-sm rounded-4 bg-body">
  <div class="card-header bg-transparent border-0 p-4 pb-0 d-flex justify-content-between align-items-center">
    <h5 class="fw-bold mb-0">Order & Pembayaran Terbaru</h5>
    <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-primary btn-sm rounded-pill">Lihat Semua →</a>
  </div>
  <div class="card-body p-4">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr class="text-secondary small">
            <th>Order #</th>
            <th>Client</th>
            <th>Paket</th>
            <th>Total</th>
            <th>Status Pembayaran</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($recentOrders as $ord)
            <tr>
              <td class="fw-bold">{{ $ord->order_number }}</td>
              <td>
                <div class="fw-semibold">{{ $ord->user->name }}</div>
                <div class="small text-secondary">{{ $ord->user->nomor_hp }}</div>
              </td>
              <td><span class="badge bg-primary-subtle text-primary rounded-pill">{{ $ord->package->name }}</span></td>
              <td class="fw-bold">Rp {{ number_format($ord->amount, 0, ',', '.') }}</td>
              <td>
                @if($ord->received_amount !== null)<span class="badge bg-primary">{{ $ord->payment_label }}</span>
                @elseif($ord->payment_status === 'pending')
                  <span class="badge bg-secondary rounded-pill">Pending</span>
                @elseif($ord->payment_status === 'paid')
                  <span class="badge bg-warning text-dark rounded-pill">Perlu Verification</span>
                @elseif($ord->payment_status === 'approved')
                  <span class="badge bg-success rounded-pill">Approved</span>
                @elseif($ord->payment_status === 'rejected')
                  <span class="badge bg-danger rounded-pill">Rejected</span>
                @endif
              </td>
              <td>
                <a href="{{ route('admin.orders.show', $ord->id) }}" class="btn btn-sm btn-outline-primary rounded-pill">Detail / Action</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-secondary py-4">Belum ada order masuk.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
