@extends('layouts.app')

@section('title', 'Kalkulator Nilai UT - Nugasin Class')

@section('content')
<main class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 p-md-5">
          <div class="text-center mb-4">
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-2">Simulasi Penilaian UT</span>
            <h1 class="h3 fw-bold mb-1">Kalkulator Nilai Akhir Mata Kuliah</h1>
            <p class="text-secondary mb-0">Hitung estimasi Nilai Akhir (NA) berdasarkan skema Tutorial Online (Tuton) & UAS.</p>
          </div>

          <form id="calcForm">
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label for="scoreTuton" class="form-label fw-semibold">Rata-rata Nilai Tuton (30%)</label>
                <input type="number" min="0" max="100" step="any" class="form-control rounded-3" id="scoreTuton" placeholder="Contoh: 85" required>
                <div class="form-text">Gabungan nilai partisipasi, diskusi 1-8, dan tugas 1-3.</div>
              </div>

              <div class="col-12 col-md-6">
                <label for="scoreUas" class="form-label fw-semibold">Prediksi Nilai UAS (70%)</label>
                <input type="number" min="0" max="100" step="any" class="form-control rounded-3" id="scoreUas" placeholder="Contoh: 75" required>
                <div class="form-text">Minimal 30% dari skor maksimal UAS agar nilai Tuton dihitung.</div>
              </div>
            </div>

            <div class="d-grid mt-4">
              <button type="submit" id="btnCalculate" class="btn btn-primary btn-lg rounded-pill fw-bold">Hitung Estimasi Nilai</button>
            </div>
          </form>

          <div id="calcResult" class="mt-4 p-4 border rounded-4 bg-body-tertiary d-none" role="status" aria-live="polite">
            <h5 class="fw-bold mb-3">Hasil Estimasi Nilai</h5>
            <div class="row text-center g-3">
              <div class="col-6">
                <div class="small text-secondary">Nilai Akhir (NA)</div>
                <div id="naScore" class="display-6 fw-bold text-primary">-</div>
              </div>
              <div class="col-6">
                <div class="small text-secondary">Grade Simulasi</div>
                <div id="naGrade" class="display-6 fw-bold text-success">-</div>
              </div>
            </div>
            <div id="naNote" class="small text-secondary text-center mt-3"></div>
            <p class="small text-secondary text-center mt-2 mb-0">Grade memakai skala simulasi: A ≥ 80, A- ≥ 75, B ≥ 70, B- ≥ 60, C ≥ 55, C- ≥ 50, D ≥ 40, E &lt; 40. Nilai dan grade resmi mengikuti ketentuan mata kuliah.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/kalkulator.js') }}?v=20260917-2" defer></script>
@endpush
