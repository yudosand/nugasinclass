@extends('layouts.app')

@section('title', 'Akses Ujian - Nugasin Class')

@section('content')
<main class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 p-md-5">
          <div class="text-center mb-4">
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-2">CAT Examination</span>
            <h1 class="h3 fw-bold mb-1">Akses Ujian Simulasi</h1>
            <p class="text-secondary">Masukkan ID Ujian unik yang kamu dapatkan setelah checkout.</p>
          </div>

          <form id="formAccess">
            <div class="mb-4">
              <label for="inputAccessId" class="form-label fw-semibold">ID Ujian Unik</label>
              <input type="text" class="form-control form-control-lg rounded-3 font-monospace text-center text-uppercase fw-bold" id="inputAccessId" placeholder="Contoh: NC-EXAM-XXXX" required>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold">Mulai Ujian Sekarang →</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/nc-access.js') }}" defer></script>
<script src="{{ asset('assets/js/access.js') }}" defer></script>
@endpush
