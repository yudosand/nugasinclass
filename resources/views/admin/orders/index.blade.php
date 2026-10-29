@extends('layouts.admin')

@section('title', 'Kelola Orders & Payments - Admin Backoffice')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h2 class="fw-bold mb-1">Orders & Payments</h2>
    <p class="text-secondary mb-0">Verifikasi dan periksa bukti pembayaran dari client.</p>
  </div>
</div>

@include('partials.form-errors')
@if($cashReady)
  @include('admin.cash.report')
@else
  <div class="alert alert-info">Cocokkan pembayaran nyata dan pencairan tim agar saldo kas serta pembayaran sebagian tercatat akurat. <a href="{{ route('admin.cash.setup') }}" class="btn btn-primary btn-sm rounded-pill ms-2">Cocokkan Kas Awal</a></div>
@endif
<div class="card border-0 shadow-sm rounded-4 bg-body mb-4">
  <div class="card-body p-4">
    <form action="{{ route('admin.orders.index') }}" method="GET" class="row g-3">
      <div class="col-12 col-md-8">
        <select name="status" class="form-select rounded-3" onchange="this.form.submit()">
          <option value="">-- Semua Status Pembayaran --</option>
          @if($cashReady)<option value="partial" @selected(request('status') === 'partial')>Dibayar Sebagian</option>@endif
          <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Perlu Verifikasi (Paid & Uploaded)</option>
          <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ $cashReady ? 'Lunas' : 'Approved' }}</option>
          <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Payment</option>
          <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
      </div>
      <div class="col-12 col-md-4">
        <button type="submit" class="btn btn-primary w-100 rounded-3">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card border-0 shadow-sm rounded-4 bg-body">
  <div class="card-body p-4">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr class="text-secondary small">
            <th>Order #</th>
            <th>Client</th>
            <th>Paket</th>
            <th>Nominal</th>
            <th>Bukti Transfer</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($orders as $ord)
            <tr>
              <td class="fw-bold">{{ $ord->order_number }}</td>
              <td>
                <div class="fw-semibold">{{ $ord->user->name }}</div>
                <div class="small text-secondary">{{ $ord->user->nomor_hp }}</div>
              </td>
              <td><span class="badge bg-primary-subtle text-primary rounded-pill">{{ $ord->package->name }}</span></td>
              <td><div class="fw-bold text-primary">Rp {{ number_format($ord->amount, 0, ',', '.') }}</div>
                @if($ord->is_test_order)<div class="small text-warning-emphasis">Uji — harga acuan, tanpa uang masuk</div>@elseif($cashReady)<div class="small">Diterima: {{ \App\Support\Cuanin::format($ord->received_amount) }}<br>Sisa: {{ \App\Support\Cuanin::format($ord->outstanding_amount) }}</div>@endif
                @if($ord->relationLoaded('distribution') && $ord->distribution)
                <div class="small text-secondary">Pool admin dibagi {{ $ord->distribution->admin_count }}: {{ \App\Support\Cuanin::format($ord->distribution->admin_pool) }}<br>Referral: {{ \App\Support\Cuanin::format($ord->distribution->commission) }}<br>Bonus: {{ \App\Support\Cuanin::format($ord->distribution->bonus) }}</div>
                @endif
              </td>
              <td>
                @if($ord->payment_proof)
                  <a href="{{ route('payment.proof', $ord->id) }}" target="_blank" class="btn btn-sm btn-outline-info rounded-pill">📷 Lihat Bukti</a>
                @else
                  <span class="text-secondary">Belum Upload</span>
                @endif
              </td>
              <td>
                @if($ord->is_test_order)
                  <span class="badge bg-warning text-dark">{{ $ord->payment_label }}</span>
                @elseif($cashReady)
                  <span class="badge {{ $ord->outstanding_amount === 0 ? 'bg-success' : 'bg-warning text-dark' }}">{{ $ord->payment_label }}</span>@if($ord->payment_status === 'paid')<div class="small">Bukti menunggu verifikasi</div>@endif
                @elseif($ord->payment_status === 'paid')
                  <span class="badge bg-warning text-dark rounded-pill">Perlu Verifikasi</span>
                @elseif($ord->payment_status === 'approved')
                  <span class="badge bg-success rounded-pill">Approved</span>
                @elseif($ord->payment_status === 'rejected')
                  <span class="badge bg-danger rounded-pill">Rejected</span>
                @else
                  <span class="badge bg-secondary rounded-pill">Pending Payment</span>
                @endif
              </td>
              <td>
                <a href="{{ route('admin.orders.show', $ord->id) }}" class="btn btn-sm btn-primary rounded-pill">Periksa & Process →</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-secondary py-4">Data order tidak ditemukan.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      {{ $orders->links() }}
    </div>
  </div>
</div>
@endsection
