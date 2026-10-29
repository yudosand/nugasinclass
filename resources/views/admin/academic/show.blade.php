@extends('layouts.admin')

@section('title', 'Kelola Progress Sesi 1-8 - Admin Backoffice')

@section('content')
<div class="mb-4">
  <a href="{{ route('admin.academic.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill mb-2">← Kembali ke Academic Progress</a>
  <h2 class="fw-bold mb-1">Academic Progress Client: {{ $client->name }}</h2>
  <p class="text-secondary mb-0">Kelola mata kuliah dan perbarui status & nilai dari Sesi 1 sampai Sesi 8.</p>
</div>

<!-- Client Info Card -->
@include('partials.form-errors')
<div class="card border-0 shadow-sm rounded-4 bg-body mb-4">
  <div class="card-body p-4">
    <div class="row align-items-center g-3">
      <div class="col-12 col-md-8">
        <div class="d-flex align-items-center gap-3">
          <div class="fs-2">🎓</div>
          <div>
            <h4 class="fw-bold mb-0">{{ $client->name }} ({{ $client->nomor_hp }})</h4>
            <div class="text-secondary">
              @if($client->profile)
                {{ $client->profile->universitas }} • {{ $client->profile->program_studi }} (Semester {{ $client->profile->semester }}) • NIM: {{ $client->profile->nim }}
              @else
                Belum melengkapi data onboarding
              @endif
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4 text-md-end">
        <div class="mb-3">
          @if($client->handlerAdmin)
            <div class="small text-secondary mb-1">Di-handle oleh</div>
            <span class="badge {{ (int) $client->handler_admin_id === (int) auth()->id() ? 'bg-success' : 'bg-primary-subtle text-primary' }} rounded-pill px-3 py-2">
              {{ (int) $client->handler_admin_id === (int) auth()->id() ? 'Saya' : $client->handlerAdmin->name }}
            </span>
            @if((int) $client->handler_admin_id === (int) auth()->id())
              <form method="POST" action="{{ route('admin.academic.release', $client->id) }}" class="mt-2">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-secondary rounded-pill px-4" data-confirm="Lepas client {{ $client->name }} agar admin lain bisa ambil alih?" onclick="return confirm(this.dataset.confirm)">Lepas Client</button>
              </form>
            @endif
          @else
            <form method="POST" action="{{ route('admin.academic.claim', $client->id) }}">
              @csrf
              <button type="submit" class="btn btn-outline-success rounded-pill px-4 fw-bold mb-2">Ambil Client</button>
            </form>
            <div class="small text-secondary">Belum ada admin yang handle client ini.</div>
          @endif
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#addCourseModal">+ Tambah Mata Kuliah</button>
        <button type="button" class="btn btn-outline-primary rounded-pill px-4 mt-2" data-bs-toggle="modal" data-bs-target="#bulkCoursesModal">+ Tambah Banyak Mata Kuliah</button>
      </div>
    </div>
  </div>
</div>

@if($client->courses->isEmpty())
  <div class="card border-0 shadow-sm rounded-4 text-center p-5 bg-body">
    <div class="fs-1 mb-2">📚</div>
    <h5 class="fw-bold">Belum Ada Mata Kuliah untuk Client Ini</h5>
    <p class="text-secondary">Gunakan "Tambah Banyak Mata Kuliah" untuk menempel daftar sekaligus, atau "Tambah Mata Kuliah" untuk input satu per satu.</p>
  </div>
@else
  <div class="card border-0 shadow-sm rounded-4 bg-body mb-4" id="bulk-session-card">
    <div class="card-body p-4">
      <h3 class="h5 fw-bold">Update Sesi Semua Mata Kuliah</h3>
      <p class="text-secondary">Terapkan status pada satu sesi di seluruh <strong>{{ $client->courses->count() }} mata kuliah {{ $client->name }}</strong>. Nilai dan catatan yang sudah diisi tetap tersimpan.</p>
      <form action="{{ route('admin.academic.session.update', $client->id) }}" method="POST" class="row g-3">
        @csrf @method('PUT')
        <div class="col-12 col-md-4"><label for="bulk_session" class="form-label fw-semibold">Sesi yang diperbarui</label>
          <select id="bulk_session" name="bulk_session" class="form-select" required><option value="">Pilih sesi</option>@for($number = 1; $number <= 8; $number++)<option value="{{ $number }}" @selected((string) old('bulk_session') === (string) $number)>Sesi {{ $number }}</option>@endfor</select>
        </div>
        @foreach(['discussion' => 'Diskusi', 'assignment' => 'Tugas'] as $field => $label)
        <div class="col-12 col-md-4"><label for="bulk_{{ $field }}_status" class="form-label fw-semibold">Status {{ $label }}</label>
          <select id="bulk_{{ $field }}_status" name="bulk_{{ $field }}_status" class="form-select" required>
            <option value="keep" @selected(old('bulk_'.$field.'_status', 'Selesai') === 'keep')>Biarkan status saat ini</option>
            @foreach(['Belum Dikerjakan', 'On Progress', 'Menunggu Nilai', 'Selesai'] as $status)<option value="{{ $status }}" @selected(old('bulk_'.$field.'_status', 'Selesai') === $status)>{{ $status }}</option>@endforeach
          </select>
        </div>
        @endforeach
        <div class="col-12 d-flex flex-wrap align-items-center gap-3"><button class="btn btn-primary rounded-pill">Terapkan ke {{ $client->courses->count() }} Mata Kuliah</button><span class="small text-secondary">Berlaku hanya untuk client ini dan sesi yang dipilih.</span></div>
      </form>
    </div>
  </div>
  <div class="row g-4">
    @foreach($client->courses as $course)
      <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 bg-body">
          <div class="card-header bg-transparent border-0 p-4 pb-0 d-flex justify-content-between align-items-center">
            <div>
              <h4 class="fw-bold mb-0 text-primary">{{ $course->course_name }}</h4>
              @if($course->course_code)
                <div class="small text-secondary">{{ $course->course_code }}</div>
              @endif
            </div>
            <form action="{{ route('admin.academic.courses.destroy', $course->id) }}" method="POST">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill" data-confirm="Hapus mata kuliah {{ $course->course_name }} beserta seluruh progress Sesi 1-8?" onclick="return confirm(this.dataset.confirm)">
                🗑️ Hapus Mata Kuliah
              </button>
            </form>
          </div>

          <div class="card-body p-4">
            <div class="accordion accordion-flush" id="accCourse{{ $course->id }}">
              @for($session = 1; $session <= 8; $session++)
                @php
                  $progress = $course->progresses->firstWhere('session_number', $session);
                @endphp
                <div class="accordion-item border rounded-3 mb-2 overflow-hidden">
                  <h2 class="accordion-header" id="heading{{ $course->id }}_{{ $session }}">
                    <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $course->id }}_{{ $session }}">
                      <div class="d-flex flex-wrap justify-content-between align-items-center w-100 me-3">
                        <div class="fw-bold fs-6 text-body">
                          📌 Sesi {{ $session }}
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                          <span class="small text-secondary">Diskusi:</span>
                          <span class="badge bg-secondary rounded-pill small">{{ $progress->discussion_status ?? 'Belum' }}</span>
                          <span class="fw-bold text-primary me-3">({{ $progress->discussion_score ?? '-' }})</span>

                          <span class="small text-secondary">Tugas:</span>
                          <span class="badge bg-secondary rounded-pill small">{{ $progress->assignment_status ?? 'Belum' }}</span>
                          <span class="fw-bold text-primary">({{ $progress->assignment_score ?? '-' }})</span>
                        </div>
                      </div>
                    </button>
                  </h2>
                  <div id="collapse{{ $course->id }}_{{ $session }}" class="accordion-collapse collapse" data-bs-parent="#accCourse{{ $course->id }}">
                    <div class="accordion-body p-4 bg-body-tertiary">
                      @if($progress)
                        <form action="{{ route('admin.academic.progress.update', $progress->id) }}" method="POST">
                          @csrf
                          @method('PUT')

                          <div class="row g-3">
                            <!-- Diskusi -->
                            <div class="col-12 col-md-6">
                              <div class="p-3 bg-body rounded-3 border">
                                <h6 class="fw-bold text-primary mb-3">💬 Status & Nilai Diskusi Sesi {{ $session }}</h6>

                                <div class="mb-3">
                                  <label class="form-label small fw-semibold">Status Diskusi</label>
                                  <select name="discussion_status" class="form-select rounded-3" required>
                                    @foreach(['Belum Dikerjakan', 'On Progress', 'Menunggu Nilai', 'Selesai'] as $st)
                                      <option value="{{ $st }}" {{ $progress->discussion_status === $st ? 'selected' : '' }}>{{ $st }}</option>
                                    @endforeach
                                  </select>
                                </div>

                                <div>
                                  <label class="form-label small fw-semibold">Nilai Diskusi (0 - 100)</label>
                                  <input type="number" min="0" max="100" name="discussion_score" class="form-control rounded-3" value="{{ $progress->discussion_score }}" placeholder="e.g. 95">
                                </div>
                              </div>
                            </div>

                            <!-- Tugas -->
                            <div class="col-12 col-md-6">
                              <div class="p-3 bg-body rounded-3 border">
                                <h6 class="fw-bold text-primary mb-3">📝 Status & Nilai Tugas Sesi {{ $session }}</h6>

                                <div class="mb-3">
                                  <label class="form-label small fw-semibold">Status Tugas</label>
                                  <select name="assignment_status" class="form-select rounded-3" required>
                                    @foreach(['Belum Dikerjakan', 'On Progress', 'Menunggu Nilai', 'Selesai'] as $st)
                                      <option value="{{ $st }}" {{ $progress->assignment_status === $st ? 'selected' : '' }}>{{ $st }}</option>
                                    @endforeach
                                  </select>
                                </div>

                                <div>
                                  <label class="form-label small fw-semibold">Nilai Tugas (0 - 100)</label>
                                  <input type="number" min="0" max="100" name="assignment_score" class="form-control rounded-3" value="{{ $progress->assignment_score }}" placeholder="e.g. 90">
                                </div>
                              </div>
                            </div>

                            <!-- Notes -->
                            <div class="col-12">
                              <label class="form-label small fw-semibold">Catatan Khusus Sesi {{ $session }} untuk Client</label>
                              <input type="text" name="notes" class="form-control rounded-3" value="{{ $progress->notes }}" placeholder="Contoh: Tugas 1 sudah disubmit ke e-learning, diskusi revisi selesai.">
                            </div>

                            <div class="col-12 text-end">
                              <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Progress Sesi {{ $session }}</button>
                            </div>
                          </div>
                        </form>
                      @endif
                    </div>
                  </div>
                </div>
              @endfor
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>
@endif

<div class="modal fade" id="bulkCoursesModal" tabindex="-1" aria-labelledby="bulkCoursesTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content rounded-4 border-0">
      <div class="modal-header">
        <h5 id="bulkCoursesTitle" class="modal-title fw-bold">Tambah Banyak Mata Kuliah</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body p-4">
        <p>Tambahkan mata kuliah untuk <strong>{{ $client->name }}</strong>. Tempel satu nama per baris, maksimal 50 mata kuliah.</p>
        <form id="bulkCoursesForm" action="{{ route('admin.academic.courses.bulk', $client->id) }}" method="POST">
          @csrf
          <label for="course_list" class="form-label fw-semibold">Daftar mata kuliah</label>
          <textarea id="course_list" name="course_list" rows="10" maxlength="20000" class="form-control" required aria-describedby="courseListHelp" placeholder="Analisis dan Perancangan Sistem&#10;Basis Data&#10;Pemrograman Berbasis Web">{{ old('course_list') }}</textarea>
          <div id="courseListHelp" class="form-text">Kode opsional. Jika ingin menyertakan kode, tulis: STSI4105 | Basis Data. Jangan sertakan persentase progres atau teks tombol dari e-learning.</div>
          @error('course_list')<div class="text-danger mt-2" role="alert">{{ $message }}</div>@enderror
          <div class="alert alert-info small mt-3 mb-0">Sesi 1–8 otomatis dibuat dengan status Belum Dikerjakan. Nama atau kode yang sudah ada pada client ini akan dilewati; nilai dan progres lama tetap tersimpan.</div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
        <button type="submit" form="bulkCoursesForm" class="btn btn-primary rounded-pill">Simpan Semua Mata Kuliah</button>
      </div>
    </div>
  </div>
</div>
@if($errors->has('course_list'))
  @push('scripts')
  <script>new bootstrap.Modal(document.getElementById('bulkCoursesModal')).show();</script>
  @endpush
@endif

<!-- Modal Add Course -->
<div class="modal fade" id="addCourseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0">
      <form action="{{ route('admin.academic.courses.store', $client->id) }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Tambah Mata Kuliah</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nama Mata Kuliah</label>
            <input type="text" name="course_name" class="form-control rounded-3" placeholder="Contoh: Rekayasa Perangkat Lunak" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Kode Mata Kuliah (Opsional)</label>
            <input type="text" name="course_code" class="form-control rounded-3" placeholder="Contoh: MSIM4301">
          </div>
          <div class="alert alert-info small rounded-3 mb-0">
            Sistem secara otomatis akan meng-generate baris progress untuk <strong>Sesi 1 sampai Sesi 8</strong> secara otomatis.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Mata Kuliah</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
