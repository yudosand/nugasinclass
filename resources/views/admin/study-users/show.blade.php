@extends('layouts.admin')

@section('title', 'Detail Pengguna Study Tracker - Admin Backoffice')

@section('content')
<a class="btn btn-outline-secondary btn-sm rounded-pill mb-3" href="{{ route('admin.study-users.index') }}">Kembali ke Daftar Pengguna</a>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <h1 class="h2 fw-bold mb-1">{{ $student->name }}</h1>
    <p class="text-secondary mb-0">{{ $student->nomor_hp }} • {{ $student->email ?: 'Tanpa email' }}</p>
  </div>
  <form method="POST" action="{{ route('admin.study-users.status', $student->id) }}">
    @csrf @method('PUT')
    <input type="hidden" name="status" value="{{ $student->status === 'approved' ? 'rejected' : 'approved' }}">
    <button class="btn btn-outline-primary rounded-pill">{{ $student->status === 'approved' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
  </form>
</div>

<a class="btn btn-outline-primary rounded-pill mb-4" href="{{ route('admin.accounts.reset', $student->id) }}">Reset Password</a>
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-secondary">Mata kuliah</div><div class="fs-3 fw-bold">{{ $stats['courses'] }}</div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-secondary">Sesi dicatat</div><div class="fs-3 fw-bold">{{ $stats['sessions'] }}</div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-secondary">Sesi selesai</div><div class="fs-3 fw-bold">{{ $stats['done'] }}</div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-secondary">Overdue</div><div class="fs-3 fw-bold text-danger">{{ $stats['overdue'] }}</div></div></div></div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
  <div class="card-body p-4">
    <h2 class="h5 fw-bold mb-3">Mata Kuliah</h2>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead><tr><th>Mata Kuliah</th><th>Sesi Aktif</th><th>Target</th><th>Progress Ringkas</th></tr></thead>
        <tbody>
        @forelse($student->studyCourses as $course)
          @php($done = $course->sessions->filter(fn($s) => $s->attendance_done && $s->discussion_status === 'Selesai' && (!$s->assignment_enabled || $s->assignment_status === 'Selesai'))->count())
          <tr>
            <td><div class="fw-semibold">{{ $course->course_name }}</div><div class="small text-secondary">{{ $course->course_code ?: '-' }}</div></td>
            <td>Sesi {{ $course->active_session }}</td>
            <td>{{ $course->target_grade ?: '-' }}</td>
            <td>{{ $done }}/8 sesi selesai</td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-center text-secondary py-4">Pengguna belum menambahkan mata kuliah.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card border-danger-subtle shadow-sm rounded-4">
  <div class="card-body p-4">
    <h2 class="h5 fw-bold text-danger">Hapus Pengguna Study Tracker</h2>
    <p class="text-secondary">Penghapusan akan menghapus mata kuliah dan sesi tracker pengguna ini.</p>
    <form method="POST" action="{{ route('admin.study-users.destroy', $student->id) }}" class="row g-3">
      @csrf @method('DELETE')
      <div class="col-12 col-md-4"><label class="form-label">Ketik nomor HP pengguna</label><input class="form-control" name="confirm_phone" required></div>
      <div class="col-12 col-md-4"><label class="form-label">Password admin Anda</label><input class="form-control" name="current_password" type="password" required autocomplete="current-password"></div>
      <div class="col-12 col-md-4 d-flex align-items-end"><button class="btn btn-outline-danger rounded-pill" onclick="return confirm('Hapus pengguna Study Tracker ini?')">Hapus Pengguna</button></div>
    </form>
  </div>
</div>
@endsection
