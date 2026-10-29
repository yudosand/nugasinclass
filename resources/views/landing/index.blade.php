@extends('layouts.app')

@section('title', 'Nugasin Class - Solusi Akademikmu')

@section('content')
<header class="hero">
  <div class="container py-5">
    <div class="row align-items-center g-4">
      <div class="col-12 col-lg-7">
        <div class="badge badge-soft rounded-pill px-3 py-2 mb-3">Solusi akademik cepat & rapi</div>
        <h1 class="display-6 fw-extrabold mb-3" style="font-weight:800;">
          Solusi Cerdas, <span class="text-primary-nc">Nugas Jadi Ringkas</span>
        </h1>
        <p class="text-secondary mb-4" style="max-width: 52ch;">
          Gak perlu pusing sama tugas numpuk. Pilih layanan yang kamu butuhkan dan biarkan kami bantu perjalanan akademikmu.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <a class="btn btn-primary rounded-pill px-4" href="{{ route('register') }}">Daftar & Pilih Paket</a>
          <a class="btn btn-outline-secondary rounded-pill px-4" href="#layanan">Lihat Layanan</a>
        </div>
      </div>
      <div class="col-12 col-lg-5">
        <div class="p-4 bg-soft-primary rounded-4 border">
          <h5 class="fw-bold mb-2">Butuh cepat? Pilih yang sesuai</h5>
          <p class="text-secondary mb-0">Semua layanan punya format rapi, komunikasi cepat, dan deadline aman.</p>
        </div>
      </div>
    </div>
  </div>
</header>

<main class="container py-5" id="layanan">
  <div class="row g-3 g-lg-4">
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card h-100 border-0 shadow-sm card-hover">
        <div class="card-body">
          <div class="fs-3">📘</div>
          <h5 class="fw-bold mt-2">Ikut Kelas Nugasin</h5>
          <p class="text-secondary">Belajar materi Tugas Praktikum SI secara intensif dengan tutor yang asik.</p>
        </div>
        <div class="card-footer bg-body border-0 pt-0 pb-4 px-3">
          <a class="btn btn-outline-primary w-100 rounded-3" href="{{ route('register') }}">Pilih Paket</a>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
      <div class="card h-100 border-0 shadow-sm card-hover">
        <div class="card-body">
          <div class="fs-3">✍️</div>
          <h5 class="fw-bold mt-2">Simulasi UAS</h5>
          <p class="text-secondary">Asah kemampuanmu dengan try-out simulasi UAS sesuai pola soal sesungguhnya.</p>
        </div>
        <div class="card-footer bg-body border-0 pt-0 pb-4 px-3">
          <a class="btn btn-primary w-100 rounded-3" href="{{ route('landing.simulasi') }}">Mulai Ujian</a>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
      <div class="card h-100 border-0 shadow-sm card-hover">
        <div class="card-body">
          <div class="fs-3">📅</div>
          <h5 class="fw-bold mt-2">Study Tracker</h5>
          <p class="text-secondary">Pantau absensi, diskusi, tugas, nilai, dan deadline e-learning dalam satu tempat.</p>
        </div>
        <div class="card-footer bg-body border-0 pt-0 pb-4 px-3">
          <a class="btn btn-outline-primary w-100 rounded-3" href="{{ route('study.landing') }}">Mulai Tracking</a>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
      <div class="card h-100 border-0 shadow-sm card-hover">
        <div class="card-body">
          <div class="fs-3">🧮</div>
          <h5 class="fw-bold mt-2">Kalkulator Nilai</h5>
          <p class="text-secondary">Hitung prediksi nilai akhir mata kuliahmu sesuai standar penilaian universitas.</p>
        </div>
        <div class="card-footer bg-body border-0 pt-0 pb-4 px-3">
          <a class="btn btn-outline-primary w-100 rounded-3" href="{{ route('landing.kalkulator') }}">Hitung Nilai</a>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection
