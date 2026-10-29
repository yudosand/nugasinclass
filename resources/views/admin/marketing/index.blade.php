@extends('layouts.admin')
@section('title', 'Daftar Marketing - Nugasin Class')
@section('content')
<div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-4">
  <div><h1 class="h3 fw-bold mb-1">Daftar Marketing</h1><p class="text-secondary mb-0">Lihat siapa saja yang bergabung sebagai Nugasin Partner.</p></div>
  <div class="card border-0 bg-primary text-white rounded-4"><div class="card-body px-4 py-3"><div>Jumlah Marketing</div><div class="h2 fw-bold mb-0">{{ $totalMarketing }}</div></div></div>
</div>
@include('partials.form-errors')
@unless($ready)<div class="alert alert-info">Cuanin belum aktif. <a href="{{ route('cuanin.index') }}">Aktifkan Cuanin</a> untuk membuka pendaftaran marketing dan data referral.</div>@endunless
<div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
  <form method="GET" action="{{ route('admin.marketing.index') }}" class="d-flex flex-wrap gap-2 mb-3">
    <input class="form-control flex-grow-1 w-auto" name="search" value="{{ $search }}" maxlength="100" placeholder="Cari nama, email, atau kode referral" aria-label="Cari marketing">
    <button class="btn btn-outline-primary">Cari</button>
    @if($search !== '')<a class="btn btn-outline-secondary" href="{{ route('admin.marketing.index') }}">Reset</a>@endif
  </form>
  <p class="small text-secondary">{{ $marketing->total() }} marketing ditemukan dari {{ $totalMarketing }} akun.</p>
  <div class="table-responsive"><table class="table align-middle">
    <thead><tr><th>Nama</th><th>Email</th><th>Kode Referral</th><th>Client Berhasil</th><th>Terdaftar</th><th>Aksi</th></tr></thead>
    <tbody>
    @forelse($marketing as $member)
      <tr><td class="fw-semibold">{{ $member->name }}</td><td>{{ $member->email }}</td><td><span class="badge bg-primary-subtle text-primary">{{ $member->referral_code ?? '—' }}</span></td><td>{{ $member->successful_clients ?? 0 }}</td><td>{{ $member->created_at->timezone('Asia/Jakarta')->format('d M Y') }}</td><td><div class="d-flex flex-wrap gap-2"><a class="btn btn-sm btn-outline-primary rounded-pill" href="{{ route('admin.accounts.reset', $member->id) }}">Reset Password</a><a class="btn btn-sm btn-outline-danger rounded-pill" href="{{ route('admin.marketing.delete', $member->id) }}">Hapus Marketing</a></div></td></tr>
    @empty
      <tr><td colspan="6" class="text-center text-secondary py-4">Marketing tidak ditemukan.</td></tr>
    @endforelse
    </tbody>
  </table></div>
  {{ $marketing->links() }}
</div></div>
@endsection
