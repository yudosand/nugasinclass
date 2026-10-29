@extends('layouts.app')

@section('title', 'Dashboard Study Tracker - Nugasin Class')

@section('content')
<main class="container py-4 py-lg-5">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
      <h1 class="h2 fw-bold mb-1">Study Tracker</h1>
      <p class="text-secondary mb-0">Catat progres e-learning kamu tanpa perlu buka banyak catatan.</p>
    </div>
    <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addCourseModal">+ Tambah Mata Kuliah</button>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-secondary">Mata kuliah</div><div class="fs-3 fw-bold">{{ $summary['courses'] }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-secondary">Diskusi belum selesai</div><div class="fs-3 fw-bold">{{ $summary['pending_discussion'] }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-secondary">Deadline dekat</div><div class="fs-3 fw-bold">{{ $summary['upcoming_assignments'] }}</div>@if($summary['overdue_assignments'])<div class="small text-danger">{{ $summary['overdue_assignments'] }} overdue</div>@endif</div></div></div>
    <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-secondary">Progress</div><div class="fs-3 fw-bold">{{ $summary['progress'] }}%</div><div class="progress" style="height:6px"><div class="progress-bar bg-success" style="width:{{ $summary['progress'] }}%"></div></div></div></div></div>
  </div>

  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
      <h2 class="h5 fw-bold mb-3">Mata Kuliah Saya</h2>
      <div class="row g-3">
        @forelse($courses as $course)
          @php
            $sessions = $course->sessions;
            $done = $sessions->filter(fn($s) => $s->attendance_done && $s->discussion_status === 'Selesai' && (!$s->assignment_enabled || $s->assignment_status === 'Selesai'))->count();
            $progress = (int) round(($done / 8) * 100);
            $due = $sessions->filter(fn($s) => $s->assignment_enabled && $s->assignment_status !== 'Selesai' && $s->assignment_deadline && $s->assignment_deadline->isFuture() && $s->assignment_deadline->lte(now()->addDays(3)))->count();
          @endphp
          <div class="col-12 col-md-6 col-xl-4">
            <div class="border rounded-4 p-3 h-100">
              <div class="d-flex justify-content-between gap-3 mb-2">
                <div><h3 class="h6 fw-bold mb-1">{{ $course->course_name }}</h3><div class="small text-secondary">{{ $course->course_code ?: 'Tanpa kode' }} @if($course->semester) • Smt {{ $course->semester }} @endif</div></div>
                <span class="badge bg-primary-subtle text-primary align-self-start">Sesi {{ $course->active_session }}</span>
              </div>
              <div class="progress mb-2" style="height:8px"><div class="progress-bar bg-success" style="width:{{ $progress }}%"></div></div>
              <div class="d-flex flex-wrap gap-2 mb-3 small"><span>{{ $progress }}% selesai</span>@if($due)<span class="badge bg-warning text-dark">{{ $due }} deadline dekat</span>@endif</div>
              <a class="btn btn-outline-primary rounded-pill w-100" href="{{ route('study.courses.show', $course->id) }}">Buka Tracker</a>
            </div>
          </div>
        @empty
          <div class="col-12"><div class="text-center py-5 text-secondary">Belum ada mata kuliah. Klik Tambah Mata Kuliah untuk mulai.</div></div>
        @endforelse
      </div>
    </div>
  </div>
</main>

<div class="modal fade" id="addCourseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0">
      <form method="POST" action="{{ route('study.courses.store') }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title fw-bold">Tambah Mata Kuliah</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body p-4">
          <div class="mb-3"><label class="form-label fw-semibold">Nama Mata Kuliah</label><input class="form-control rounded-3" name="course_name" required maxlength="255"></div>
          <div class="row g-3">
            <div class="col-6"><label class="form-label fw-semibold">Kode</label><input class="form-control rounded-3" name="course_code" maxlength="50"></div>
            <div class="col-6"><label class="form-label fw-semibold">Semester</label><input class="form-control rounded-3" name="semester" type="number" min="1" max="14"></div>
            <div class="col-6"><label class="form-label fw-semibold">Sesi aktif</label><select class="form-select rounded-3" name="active_session">@for($i=1;$i<=8;$i++)<option value="{{ $i }}">Sesi {{ $i }}</option>@endfor</select></div>
            <div class="col-6"><label class="form-label fw-semibold">Target nilai</label><input class="form-control rounded-3" name="target_grade" maxlength="5" placeholder="B / A"></div>
          </div>
          <div class="alert alert-info mt-3 mb-0 small">Sistem otomatis membuat sesi 1-8. Tugas aktif default di sesi 3, 5, dan 7.</div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary rounded-pill px-4">Simpan</button></div>
      </form>
    </div>
  </div>
</div>
@endsection
