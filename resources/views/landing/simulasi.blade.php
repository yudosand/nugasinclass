@extends('layouts.app')

@section('title', 'Simulasi UAS - Nugasin Class')

@section('content')
<main class="container py-5">
  <div class="text-center mb-5">
    <span class="badge badge-soft rounded-pill px-3 py-2 mb-2">Simulasi UAS CAT</span>
    <h1 class="fw-bold">Pilih Mata Kuliah Ujian</h1>
    <p class="text-secondary mb-0">Uji kesiapan ujianmu dengan sistem Computer Based Test (CBT) Nugasin Class.</p>
  </div>

  <div class="row g-4 justify-content-center" id="courseList">
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm rounded-4 card-hover h-100">
        <div class="card-body p-4">
          <span class="badge bg-primary-subtle text-primary rounded-pill mb-2">Sistem Informasi</span>
          <h5 class="fw-bold mb-1">Rekayasa Perangkat Lunak</h5>
          <p class="text-secondary small mb-3">MSIM4301 • 3 Sesi Ujian (150 Soal)</p>
          <div class="d-flex justify-content-between align-items-center mt-auto">
            <span class="fw-bold text-primary">Rp 15.000</span>
            <a href="{{ route('landing.checkout') }}?course=rpl" class="btn btn-primary btn-sm rounded-pill px-3">Beli Akses</a>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm rounded-4 card-hover h-100">
        <div class="card-body p-4">
          <span class="badge bg-primary-subtle text-primary rounded-pill mb-2">Sistem Informasi</span>
          <h5 class="fw-bold mb-1">Basis Data</h5>
          <p class="text-secondary small mb-3">MSIM4201 • 3 Sesi Ujian (150 Soal)</p>
          <div class="d-flex justify-content-between align-items-center mt-auto">
            <span class="fw-bold text-primary">Rp 15.000</span>
            <a href="{{ route('landing.checkout') }}?course=basisdata" class="btn btn-primary btn-sm rounded-pill px-3">Beli Akses</a>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm rounded-4 card-hover h-100">
        <div class="card-body p-4">
          <span class="badge bg-primary-subtle text-primary rounded-pill mb-2">Umum</span>
          <h5 class="fw-bold mb-1">Pancasila</h5>
          <p class="text-secondary small mb-3">MKDU4111 • 3 Sesi Ujian (150 Soal)</p>
          <div class="d-flex justify-content-between align-items-center mt-auto">
            <span class="fw-bold text-primary">Rp 15.000</span>
            <a href="{{ route('landing.checkout') }}?course=pancasila" class="btn btn-primary btn-sm rounded-pill px-3">Beli Akses</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="text-center mt-5">
    <a href="{{ route('landing.akses') }}" class="btn btn-outline-secondary rounded-pill px-4">Sudah Memiliki ID Ujian? Akses Di Sini →</a>
  </div>
</main>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/simulasi.js') }}" defer></script>
@endpush
