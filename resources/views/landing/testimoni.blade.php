@extends('layouts.app')

@section('title', 'Testimoni - Nugasin Class')

@section('content')
<header class="hero">
  <div class="container py-5 text-center">
    <h1 class="fw-bold" style="font-weight:800;">Apa Kata Mereka?</h1>
    <p class="text-secondary mb-0" style="max-width: 70ch; margin-inline: auto;">
      Ribuan mahasiswa terbantu menyelesaikan tugas, memperbaiki nilai, dan lulus tepat waktu bersama Nugasin Class.
    </p>
  </div>
</header>

<main class="container py-5">
  <div class="row g-3 g-lg-4">
    <div class="col-12 col-lg-4">
      <div class="card h-100 border-0 shadow-sm card-hover">
        <div class="card-body p-4">
          <div class="text-warning fs-5 mb-2">★★★★★</div>
          <p class="text-secondary fst-italic">
            "Awalnya ragu buat joki tugas coding Java. Tapi ternyata hasilnya rapi, dikasih penjelasan juga. Nilai Tugas 1 dapet 95!"
          </p>
          <hr>
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-soft-primary" style="width:48px;height:48px;"></div>
            <div>
              <div class="fw-bold">Rian Pratama</div>
              <div class="text-secondary small">Sistem Informasi - UT</div>
              <span class="badge badge-soft rounded-pill mt-2">Joki Tugas Praktikum</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card h-100 border-0 shadow-sm card-hover">
        <div class="card-body p-4">
          <div class="text-warning fs-5 mb-2">★★★★★</div>
          <p class="text-secondary fst-italic">
            "Fitur Simulasi UAS ngebantu banget. Soal-soalnya mirip pola ujian asli. Pas hari H jadi lebih pede."
          </p>
          <hr>
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-soft-primary" style="width:48px;height:48px;"></div>
            <div>
              <div class="fw-bold">Siti Nurhaliza</div>
              <div class="text-secondary small">Manajemen</div>
              <span class="badge badge-soft rounded-pill mt-2">Simulasi UAS</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card h-100 border-0 shadow-sm card-hover">
        <div class="card-body p-4">
          <div class="text-warning fs-5 mb-2">★★★★★</div>
          <p class="text-secondary fst-italic">
            "Adminnya fast respon, tugas selalu dikirim sebelum deadline. Semester ini IPK aman sentosa berkat Nugasin Class."
          </p>
          <hr>
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-soft-primary" style="width:48px;height:48px;"></div>
            <div>
              <div class="fw-bold">Dewi Kartika</div>
              <div class="text-secondary small">Akuntansi</div>
              <span class="badge badge-soft rounded-pill mt-2">All Prodi Package</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <section class="mt-5">
    <div class="text-center mb-4">
      <h2 class="fw-bold">Bukti Nyata & Hasil Ujian</h2>
      <p class="text-secondary mb-0">Transparansi adalah prioritas kami. Klik gambar untuk zoom.</p>
    </div>

    <div class="row g-3">
      <div class="col-12 col-md-6 col-lg-3">
        <button type="button" class="p-0 border-0 bg-transparent w-100" data-bs-toggle="modal" data-bs-target="#imageModal" data-img="{{ asset('assets/img/testimoni-1.jpg') }}" aria-label="Buka gambar testimoni 1">
          <div class="gallery-thumb border">
            <img src="{{ asset('assets/img/testimoni-1.jpg') }}" alt="Testimoni Chat 1" />
          </div>
        </button>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <button type="button" class="p-0 border-0 bg-transparent w-100" data-bs-toggle="modal" data-bs-target="#imageModal" data-img="{{ asset('assets/img/testimoni-2.jpg') }}" aria-label="Buka gambar testimoni 2">
          <div class="gallery-thumb border">
            <img src="{{ asset('assets/img/testimoni-2.jpg') }}" alt="Testimoni Chat 2" />
          </div>
        </button>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <button type="button" class="p-0 border-0 bg-transparent w-100" data-bs-toggle="modal" data-bs-target="#imageModal" data-img="{{ asset('assets/img/testimoni-3.jpg') }}" aria-label="Buka gambar testimoni 3">
          <div class="gallery-thumb border">
            <img src="{{ asset('assets/img/testimoni-3.jpg') }}" alt="Testimoni Chat 3" />
          </div>
        </button>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <button type="button" class="p-0 border-0 bg-transparent w-100" data-bs-toggle="modal" data-bs-target="#imageModal" data-img="{{ asset('assets/img/bukti-nilai.jpg') }}" aria-label="Buka gambar bukti nilai">
          <div class="gallery-thumb border">
            <img src="{{ asset('assets/img/bukti-nilai.jpg') }}" alt="Bukti Nilai" />
          </div>
        </button>
      </div>
    </div>
  </section>

  <section class="mt-5">
    <div class="p-5 rounded-4 text-center text-white" style="background:#1e293b;">
      <h3 class="fw-bold mb-2">Giliran Kamu Dapat Nilai A!</h3>
      <p class="mb-4" style="color:#cbd5e1;">Jangan biarkan tugas menumpuk menghambat kelulusanmu.</p>
      <a class="btn btn-light rounded-pill px-4" href="{{ route('register') }}">Daftar Sekarang</a>
    </div>
  </section>
</main>

<!-- Modal preview gambar -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content bg-dark">
      <div class="modal-header border-0">
        <h5 class="modal-title text-white">Preview</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <img id="modalImg" src="" alt="Preview gambar" style="width:100%;height:auto;display:block;" />
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/testimoni.js') }}" defer></script>
@endpush
