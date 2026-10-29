@extends('layouts.admin')

@section('title', 'Daftar Pengguna Study Tracker - Admin Backoffice')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <h1 class="h2 fw-bold mb-1">Daftar Pengguna Study Tracker</h1>
    <p class="text-secondary mb-0">Pantau pengguna, jumlah mata kuliah, dan akses akun tracker.</p>
  </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
  <div class="card-body p-4">
    <form method="GET" action="{{ route('admin.study-users.index') }}" class="d-flex gap-2">
      <input class="form-control rounded-3" name="search" value="{{ $search }}" placeholder="Cari nama, HP, atau email pengguna" maxlength="100">
      <button class="btn btn-outline-primary rounded-3">Cari</button>
      @if($search !== '')<a class="btn btn-outline-secondary rounded-3" href="{{ route('admin.study-users.index') }}">Reset</a>@endif
    </form>
  </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
  <div class="card-body p-4">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead><tr><th>Nama</th><th>Kontak</th><th>Mata Kuliah</th><th>Status</th><th>Daftar</th><th>Aksi</th></tr></thead>
        <tbody>
        @forelse($users as $student)
          <tr>
            <td class="fw-semibold">{{ $student->name }}</td>
            <td><div>{{ $student->nomor_hp }}</div><div class="small text-secondary">{{ $student->email ?: '-' }}</div></td>
            <td>{{ $student->study_courses_count }}</td>
            <td><span class="badge {{ $student->status === 'approved' ? 'bg-success' : 'bg-secondary' }} rounded-pill">{{ $student->status === 'approved' ? 'Aktif' : 'Nonaktif' }}</span></td>
            <td>{{ $student->created_at->timezone('Asia/Jakarta')->format('d M Y') }}</td>
            <td><a class="btn btn-sm btn-outline-primary rounded-pill" href="{{ route('admin.study-users.show', $student->id) }}">Detail</a></td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center text-secondary py-4">Belum ada pengguna Study Tracker.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    {{ $users->links() }}
  </div>
</div>
@endsection
