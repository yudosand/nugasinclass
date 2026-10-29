@extends('layouts.app')

@section('title', $course->course_name.' - Study Tracker')

@section('content')
<main class="container py-4 py-lg-5">
  <a class="btn btn-outline-secondary btn-sm rounded-pill mb-3" href="{{ route('study.dashboard') }}">Kembali ke Dashboard</a>
  <div class="row g-4">
    <div class="col-12 col-xl-4">
      <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
          <h1 class="h4 fw-bold">{{ $course->course_name }}</h1>
          <p class="text-secondary">{{ $course->course_code ?: 'Tanpa kode' }} @if($course->semester) • Semester {{ $course->semester }} @endif</p>
          <form method="POST" action="{{ route('study.courses.update', $course->id) }}" class="vstack gap-3">
            @csrf @method('PUT')
            <input class="form-control rounded-3" name="course_name" value="{{ old('course_name', $course->course_name) }}" required>
            <input class="form-control rounded-3" name="course_code" value="{{ old('course_code', $course->course_code) }}" placeholder="Kode mata kuliah">
            <div class="row g-2">
              <div class="col-4"><input class="form-control rounded-3" name="semester" value="{{ old('semester', $course->semester) }}" type="number" min="1" max="14" placeholder="Smt"></div>
              <div class="col-4"><select class="form-select rounded-3" name="active_session">@for($i=1;$i<=8;$i++)<option value="{{ $i }}" @selected($course->active_session===$i)>Sesi {{ $i }}</option>@endfor</select></div>
              <div class="col-4"><input class="form-control rounded-3" name="target_grade" value="{{ old('target_grade', $course->target_grade) }}" maxlength="5" placeholder="Target"></div>
            </div>
            <button class="btn btn-outline-primary rounded-pill">Simpan Info</button>
          </form>
        </div>
      </div>
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
          <h2 class="h5 fw-bold">Kalkulator Nilai</h2>
          <div class="table-responsive"><table class="table">
            <tr><td>Rata-rata diskusi</td><td class="text-end">{{ $grade['discussion'] !== null ? number_format($grade['discussion'], 1) : '-' }}</td></tr>
            <tr><td>Rata-rata tugas</td><td class="text-end">{{ $grade['assignment'] !== null ? number_format($grade['assignment'], 1) : '-' }}</td></tr>
            <tr><th>Prediksi saat ini</th><th class="text-end">{{ $grade['prediction'] ?? '-' }}</th></tr>
          </table></div>
          <p class="small text-secondary mb-0">Prediksi memakai rata-rata nilai yang sudah diinput. Target nilai bisa disimpan di info mata kuliah.</p>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-8">
      <div class="vstack gap-3">
        @foreach($course->sessions as $session)
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
              <form method="POST" action="{{ route('study.sessions.update', [$course->id, $session->id]) }}">
                @csrf @method('PUT')
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                  <div><h2 class="h5 fw-bold mb-0">Sesi {{ $session->session_number }}</h2>@if($course->active_session === $session->session_number)<span class="small text-primary">Sesi aktif sekarang</span>@endif</div>
                  @if($session->assignment_enabled)<span class="badge bg-warning text-dark">Ada tugas</span>@else<span class="badge bg-secondary-subtle text-secondary">Tanpa tugas</span>@endif
                </div>
                <div class="row g-3 align-items-end">
                  <div class="col-12 col-md-4"><label class="form-check"><input type="checkbox" class="form-check-input" name="attendance_done" value="1" @checked($session->attendance_done)> Absensi selesai</label></div>
                  <div class="col-12 col-md-4"><label class="form-label small fw-semibold">Status Diskusi</label><select class="form-select rounded-3" name="discussion_status">@foreach(['Belum Dikerjakan','On Progress','Menunggu Nilai','Selesai'] as $st)<option value="{{ $st }}" @selected($session->discussion_status===$st)>{{ $st }}</option>@endforeach</select></div>
                  <div class="col-12 col-md-4"><label class="form-label small fw-semibold">Nilai Diskusi</label><input class="form-control rounded-3" name="discussion_score" type="number" min="0" max="100" value="{{ $session->discussion_score }}"></div>
                  <div class="col-12"><label class="form-check"><input type="checkbox" class="form-check-input" name="assignment_enabled" value="1" @checked($session->assignment_enabled)> Ada tugas di sesi ini</label></div>
                  <div class="col-12 col-md-4"><label class="form-label small fw-semibold">Status Tugas</label><select class="form-select rounded-3" name="assignment_status">@foreach(['Belum Ada','Belum Dikerjakan','On Progress','Menunggu Nilai','Selesai'] as $st)<option value="{{ $st }}" @selected($session->assignment_status===$st)>{{ $st }}</option>@endforeach</select></div>
                  <div class="col-12 col-md-4"><label class="form-label small fw-semibold">Deadline Tugas</label><input class="form-control rounded-3" name="assignment_deadline" type="date" value="{{ $session->assignment_deadline?->format('Y-m-d') }}"></div>
                  <div class="col-12 col-md-4"><label class="form-label small fw-semibold">Nilai Tugas</label><input class="form-control rounded-3" name="assignment_score" type="number" min="0" max="100" value="{{ $session->assignment_score }}"></div>
                  <div class="col-12"><label class="form-label small fw-semibold">Catatan</label><textarea class="form-control rounded-3" name="notes" rows="2">{{ $session->notes }}</textarea></div>
                  <div class="col-12 text-end"><button class="btn btn-primary rounded-pill px-4">Update Sesi {{ $session->session_number }}</button></div>
                </div>
              </form>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</main>
@endsection
