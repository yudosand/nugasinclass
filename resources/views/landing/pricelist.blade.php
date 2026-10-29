@extends('layouts.app')

@section('title', 'Daftar Harga - Nugasin Class')

@section('content')
<header class="hero">
  <div class="container py-5">
    <div class="text-center">
      <div class="badge badge-soft rounded-pill px-3 py-2 mb-3">NUGASIN CLASS</div>
      <h1 class="fw-bold" style="font-weight:800;">Semester Helper Pack</h1>
      <p class="text-secondary mb-0">Untuk kamu yang sibuk, biar tugas tetap aman.</p>
    </div>
  </div>
</header>

<main class="container py-5">
  <div class="row g-3 g-lg-4 justify-content-center mb-5">
    @foreach($packages as $pkg)
      <div class="col-12 col-lg-4">
        <div class="card h-100 border-0 shadow-sm card-hover {{ $pkg->is_recommended ? 'border' : '' }}" style="{{ $pkg->is_recommended ? 'border-color: rgba(37,99,235,.25) !important;' : '' }}">
          <div class="card-body p-4">
            @if($pkg->is_recommended)
              <div class="d-flex justify-content-between align-items-start">
                <h5 class="fw-bold mb-0">{{ $pkg->name }}</h5>
                <span class="badge bg-soft-primary text-primary-nc rounded-pill">Rekomendasi</span>
              </div>
            @else
              <h5 class="fw-bold">{{ $pkg->name }}</h5>
            @endif
            <p class="text-secondary mb-3">{{ $pkg->description }}</p>

            <div class="display-6 fw-bold mb-3 {{ $pkg->is_recommended ? 'text-primary-nc' : '' }}">
              Rp {{ number_format($pkg->price, 0, ',', '.') }}
              <span class="fs-6 text-secondary">/ {{ $pkg->price_label ?: 'semester' }}</span>
            </div>

            @if($pkg->features)
              <div class="text-uppercase small text-secondary fw-semibold mb-2">What included</div>
              <ul class="list-unstyled mb-4">
                @foreach($pkg->features as $feat)
                  <li class="d-flex gap-2 mb-1"><span class="text-success">✓</span><span>{{ $feat }}</span></li>
                @endforeach
              </ul>
            @endif

            <a class="btn {{ $pkg->is_recommended ? 'btn-primary' : 'btn-outline-primary' }} w-100 rounded-3" href="{{ route('register', ['package_id' => $pkg->id]) }}">
              Pilih & Order Now
            </a>
          </div>
        </div>
      </div>
    @endforeach
  </div>
</main>
@endsection
