@extends('layouts.app')

@section('title', 'Study Tracker E-Learning - Nugasin Class')

@section('content')
<section class="py-5 border-bottom bg-body">
  <div class="container py-lg-4">
    <div class="row align-items-center g-4">
      <div class="col-12 col-lg-7">
        <div class="badge badge-soft rounded-pill px-3 py-2 mb-3">Planner akademik pribadi</div>
        <h1 class="display-5 fw-bold mb-3">Study Tracker E-Learning</h1>
        <p class="lead text-secondary mb-4" style="max-width:58ch">Pantau absensi, diskusi, tugas sesi 3/5/7, nilai, dan deadline kuliahmu dalam satu dashboard yang ringan.</p>
        <div class="d-flex flex-wrap gap-2">
          <a class="btn btn-primary rounded-pill px-4" href="{{ route('study.register') }}">Mulai Tracking Gratis</a>
          <a class="btn btn-outline-secondary rounded-pill px-4" href="{{ route('study.login') }}">Masuk Tracker</a>
        </div>
      </div>
      <div class="col-12 col-lg-5">
        <div class="border rounded-4 p-4 bg-body-tertiary shadow-sm">
          <strong class="d-block mb-3">Mulai dari data kuliahmu sendiri</strong>
          <div class="vstack gap-3">
            <div class="d-flex gap-3">
              <span class="badge rounded-pill bg-primary align-self-start">1</span>
              <div><div class="fw-semibold">Daftar / masuk akun</div><div class="text-secondary small">Dashboard pribadi dibuat setelah login.</div></div>
            </div>
            <div class="d-flex gap-3">
              <span class="badge rounded-pill bg-primary align-self-start">2</span>
              <div><div class="fw-semibold">Tambahkan mata kuliah</div><div class="text-secondary small">Isi nama, kode, semester, dan sesi aktif.</div></div>
            </div>
            <div class="d-flex gap-3">
              <span class="badge rounded-pill bg-primary align-self-start">3</span>
              <div><div class="fw-semibold">Pantau prioritas</div><div class="text-secondary small">Diskusi, tugas, deadline, dan progress baru muncul dari data yang kamu input.</div></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="container py-5">
  <div class="row g-3 g-lg-4">
    @foreach([
      ['title' => 'Mata Kuliah Saya', 'body' => 'Tambah mata kuliah, kode, semester, dan sesi aktif saat ini.'],
      ['title' => 'Timeline Sesi 1-8', 'body' => 'Checklist absensi, diskusi, catatan, dan tugas khusus sesi 3, 5, 7.'],
      ['title' => 'Reminder Deadline', 'body' => 'Dashboard menandai tugas yang overdue dan yang mendekati deadline.'],
      ['title' => 'Kalkulator Nilai', 'body' => 'Pantau rata-rata nilai diskusi dan tugas untuk membaca arah nilai akhir.'],
    ] as $item)
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body">
            <h5 class="fw-bold">{{ $item['title'] }}</h5>
            <p class="text-secondary mb-0">{{ $item['body'] }}</p>
          </div>
        </div>
      </div>
    @endforeach
  </div>
</section>
@endsection
