@extends('layouts.admin')

@section('title', 'Daftar Client - Admin Backoffice')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h2 class="fw-bold mb-1">Daftar Client</h2>
    <p class="text-secondary mb-0">Kelola informasi seluruh client terdaftar Nugasin Class.</p>
  </div>
</div>

<div class="card border-0 shadow-sm rounded-4 bg-body mb-4">
  <div class="card-body p-4">
    <form action="{{ route('admin.clients.index') }}" method="GET" class="row g-3">
      <div class="col-12 col-md-6">
        <input type="text" name="search" class="form-control rounded-3" placeholder="Cari nama, HP, Universitas, NIM, Prodi..." value="{{ request('search') }}">
      </div>
      <div class="col-12 col-md-4">
        <select name="status" class="form-select rounded-3">
          <option value="">-- Semua Status --</option>
          <option value="pending_payment" {{ request('status') === 'pending_payment' ? 'selected' : '' }}>Pending Payment</option>
          <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
          <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
          <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
      </div>
      <div class="col-12 col-md-2">
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
            <th>Nama Client</th>
            <th>Nomor HP</th>
            <th>Universitas & Prodi</th>
            <th>Paket</th>
            <th>Status Akun</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($clients as $client)
            <tr>
              <td>
                <div class="fw-bold">{{ $client->name }}</div>
                <div class="small text-secondary">Tergabung: {{ $client->created_at->format('d M Y') }}</div>
              </td>
              <td class="fw-semibold">{{ $client->nomor_hp }}</td>
              <td>
                @if($client->profile && $client->profile->universitas)
                  <div class="fw-semibold">{{ $client->profile->universitas }}</div>
                  <div class="small text-secondary">{{ $client->profile->program_studi }} (Smt {{ $client->profile->semester }})</div>
                @else
                  <span class="text-secondary fst-italic">Belum Onboarding</span>
                @endif
              </td>
              <td>
                @php $order = $client->orders->first(); @endphp
                @if($order && $order->package)
                  <span class="badge bg-primary-subtle text-primary rounded-pill">{{ $order->package->name }}</span>
                @else
                  <span class="text-secondary">-</span>
                @endif
              </td>
              <td>
                @if($client->status === 'approved')
                  <span class="badge bg-success rounded-pill">Approved</span>
                @elseif($client->status === 'pending_approval')
                  <span class="badge bg-warning text-dark rounded-pill">Verification Needed</span>
                @elseif($client->status === 'pending_payment')
                  <span class="badge bg-secondary rounded-pill">Pending Payment</span>
                @elseif($client->status === 'rejected')
                  <span class="badge bg-danger rounded-pill">Rejected</span>
                @endif
              </td>
              <td>
                <a href="{{ route('admin.clients.show', $client->id) }}" class="btn btn-sm btn-outline-primary rounded-pill">Detail Client</a>
                <a href="{{ route('admin.academic.show', $client->id) }}" class="btn btn-sm btn-primary rounded-pill ms-1">Kelola Academic Progress</a>
                <a href="{{ route('admin.clients.delete', $client->id) }}" class="btn btn-sm btn-outline-danger rounded-pill ms-1">Hapus</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-secondary py-4">Data client tidak ditemukan.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      {{ $clients->links() }}
    </div>
  </div>
</div>
@endsection
