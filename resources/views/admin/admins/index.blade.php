@extends('layouts.admin')
@section('title', 'Daftar Admin - Nugasin Class')
@section('content')
<div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-4">
  <div><h1 class="h3 fw-bold mb-1">Daftar Admin</h1><p class="text-secondary mb-0">Kelola akses pengelola backoffice.</p></div>
  <a class="btn btn-primary rounded-pill" href="{{ route('admin.admins.create') }}">+ Tambah Admin Baru</a>
</div>
<div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
  <form method="GET" class="d-flex gap-2 mb-3"><input class="form-control" name="search" value="{{ $search }}" maxlength="100" placeholder="Cari nama atau nomor HP admin" aria-label="Cari admin"><button class="btn btn-outline-primary">Cari</button></form>
  <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nama</th><th>Nomor HP / Login</th><th>Terdaftar</th><th>Aksi</th></tr></thead><tbody>
  @forelse($admins as $admin)
    <tr><td class="fw-semibold">{{ $admin->name }} @if($admin->id === auth()->id())<span class="badge bg-primary-subtle text-primary">Anda</span>@endif</td><td>{{ $admin->nomor_hp }}</td><td>{{ $admin->created_at->format('d M Y') }}</td><td><a class="btn btn-sm btn-outline-primary rounded-pill" href="{{ route('admin.accounts.reset', $admin->id) }}">Reset Password</a></td></tr>
  @empty<tr><td colspan="4" class="text-center text-secondary py-4">Admin tidak ditemukan.</td></tr>@endforelse
  </tbody></table></div>{{ $admins->links() }}
</div></div>
@endsection
