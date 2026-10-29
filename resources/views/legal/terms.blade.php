@extends('layouts.app')
@section('title', 'Syarat dan Ketentuan Layanan - Nugasin Class')
@section('content')
<main class="container py-5">
  <div class="row justify-content-center"><div class="col-12 col-lg-9">
    <article class="card border-0 shadow-sm rounded-4"><div class="card-body p-4 p-md-5">
      <span class="badge bg-primary-subtle text-primary rounded-pill mb-3">NUGASIN CLASS</span>
      <h1 class="h3 fw-bold">SYARAT DAN KETENTUAN LAYANAN (TERMS &amp; CONDITIONS)</h1>
      <p class="text-secondary small mb-4">Versi {{ \App\Support\ServiceTerms::VERSION }}</p>
      @include(\App\Support\ServiceTerms::VIEW)
    </div></article>
  </div></div>
</main>
@endsection
