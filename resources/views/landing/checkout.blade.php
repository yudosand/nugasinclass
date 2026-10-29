@extends('layouts.app')

@section('title', 'Checkout Simulasi UAS - Nugasin Class')

@section('content')
<main class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 p-lg-5">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
              <div class="badge badge-soft rounded-pill px-3 py-2 mb-2">Checkout Simulasi</div>
              <h1 class="h3 fw-bold mb-1">Pembayaran Akses Ujian</h1>
              <div class="text-secondary">Setelah pembayaran berhasil, kamu akan mendapatkan <span class="fw-semibold">ID Ujian (unik)</span> untuk mengakses ujian CAT.</div>
            </div>
          </div>

          <div id="checkoutAlert" class="alert alert-info d-none" role="alert"></div>

          <div class="row g-3 mt-1">
            <div class="col-12 col-lg-7">
              <div class="border rounded-4 p-3">
                <div class="small text-secondary">Mata Kuliah</div>
                <div id="courseTitle" class="fw-bold fs-5">Rekayasa Perangkat Lunak</div>
                <div class="small text-secondary mt-1">3 sesi • 50 soal/sesi (total 150)</div>
                <hr class="my-3"/>
                <div class="d-flex justify-content-between">
                  <div class="text-secondary">Harga Akses</div>
                  <div id="coursePrice" class="fw-bold text-primary fs-5">Rp 15.000</div>
                </div>
              </div>
            </div>

            <div class="col-12 col-lg-5">
              <div class="border rounded-4 p-3">
                <div class="fw-bold mb-2">Metode Pembayaran</div>
                <div class="vstack gap-2 mb-3">
                  <label class="border rounded-3 p-3 d-flex align-items-center gap-2 cursor-pointer">
                    <input type="radio" name="pay" class="form-check-input" checked>
                    <span>Instant Payment / Simulation</span>
                  </label>
                </div>

                <button id="btnPay" class="btn btn-primary w-100 rounded-pill fw-bold py-2">Bayar & Dapatkan ID Ujian</button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</main>

<!-- Success Modal -->
<div class="modal fade" id="paidModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Pembayaran Berhasil!</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="text-secondary">Simpan ID Ujian ini. 1 ID = 1 mata kuliah = 1x ujian.</div>
        <div class="border rounded-3 p-3 mt-2 d-flex justify-content-between align-items-center gap-2 bg-body-tertiary">
          <div class="fw-bold fs-5 text-primary" id="accessIdText">-</div>
          <button id="btnCopy" class="btn btn-outline-secondary btn-sm rounded-pill">Copy ID</button>
        </div>
        <div class="small text-secondary mt-2">Gunakan ID ini di halaman Akses Ujian untuk mulai ujian.</div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Tutup</button>
        <a id="btnGoAccess" class="btn btn-primary rounded-pill px-4" href="{{ route('landing.akses') }}">Ke Halaman Akses Ujian →</a>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/nc-access.js') }}" defer></script>
<script src="{{ asset('assets/js/checkout.js') }}" defer></script>
@endpush
