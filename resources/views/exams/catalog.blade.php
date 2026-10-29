@extends('layouts.app')
@section('title','Simulasi UAS - Nugasin Class')
@section('content')
<main class="container py-5">
  <div class="d-flex flex-wrap justify-content-between gap-3 mb-4"><div><span class="badge bg-primary-subtle text-primary mb-2">Belajar & Uji Kemampuan</span><h1 class="fw-bold">Simulasi UAS</h1><p class="text-secondary">Pilih mata kuliah, bayar melalui QRIS, lalu konfirmasi ke admin untuk membuka akses.</p></div><div>@auth<a class="btn btn-primary rounded-pill" href="{{ route('exams.mine') }}">Paket Saya</a>@else<a class="btn btn-outline-primary rounded-pill" href="{{ route('exams.login') }}">Masuk</a> <a class="btn btn-primary rounded-pill" href="{{ route('exams.register') }}">Daftar Simulasi UAS</a>@endauth</div></div>
  <p class="small text-secondary">Dua mode dalam satu paket: membaca materi sumber dan ujian interaktif. Skor otomatis hanya untuk soal dengan kunci yang tersedia; soal uraian dan kunci yang belum terbaca ditinjau mandiri. Materi bersumber dari bank latihan, bukan soal atau kunci UAS resmi.</p>
  @include('partials.form-errors')
  <div class="row g-4">
    @forelse($catalog as $item)
      @php($questions=\App\Support\Exams::questions($item))
      @php($scored=count(array_filter($questions,fn($q)=>!empty($q['answer']))))
      <div class="col-12 col-md-6 col-xl-4"><article class="card h-100 rounded-4 shadow-sm border-0"><div class="card-body p-4 d-flex flex-column">
        <span class="text-primary small fw-bold">{{ $item->code }}</span><h2 class="h5 fw-bold mt-2">{{ $item->title }}</h2>
        <p class="text-secondary small">{{ array_sum(array_column(json_decode($item->documents,true),'pages')) }} halaman belajar · {{ count($questions) }} soal interaktif tersedia</p>
        <p class="small text-secondary">{{ $scored }} soal dapat dinilai otomatis; {{ count($questions)-$scored }} latihan ditinjau mandiri.</p>
        <p class="fs-4 fw-bold">Rp {{ number_format($item->price,0,',','.') }}</p>
        @auth<form class="mt-auto" method="POST" action="{{ route('exams.buy',$item->id) }}">@csrf
          <input type="hidden" name="terms_version" value="{{ \App\Support\ServiceTerms::VERSION }}">
          <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="terms_accepted" id="terms{{ $item->id }}" value="1" required><label class="form-check-label small" for="terms{{ $item->id }}">Saya menyetujui <a href="{{ route('terms') }}" target="_blank" rel="noopener">Syarat & Ketentuan</a> dan akses pribadi, tidak untuk dibagikan.</label></div>
          <button class="btn btn-primary rounded-pill w-100">Beli Paket</button></form>
        @else<a class="btn btn-primary rounded-pill mt-auto" href="{{ route('exams.register') }}">Daftar & Pilih Paket</a>@endauth
      </div></article></div>
    @empty<div class="col"><div class="alert alert-info">Paket soal sedang disiapkan. Silakan cek kembali setelah paket diaktifkan.</div></div>@endforelse
  </div>
</main>
@endsection
