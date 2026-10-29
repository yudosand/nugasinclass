@extends('layouts.app')
@section('title', 'Nugasin Partner - Daftar Marketing')
@section('content')
<main>
  <section class="container py-5"><div class="row align-items-center g-5">
    <div class="col-12 col-lg-6">
      <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-3">NUGASIN PARTNER</span>
      <h1 class="display-5 fw-bold mb-3">Bantu teman.<br>Raih penghasilan bersama Nugasin.</h1>
      <p class="lead text-secondary">Rekomendasikan layanan Nugasin Class dan dapatkan <strong class="text-primary">komisi langsung 10%</strong> dari setiap transaksi berhasil melalui kode referral kamu.</p>
      <p class="text-secondary">Semakin banyak teman yang berhasil order, semakin besar bonus referral yang bisa kamu raih.</p>
      <div class="d-flex flex-wrap gap-3 mt-4">
        <a href="{{ route('marketing.register') }}" class="btn btn-primary btn-lg rounded-pill px-4">Daftar sebagai Marketing →</a>
        <a href="{{ route('marketing.login') }}" class="btn btn-outline-primary btn-lg rounded-pill">Login Marketing</a>
      </div>
      <div class="d-flex flex-wrap gap-3 small text-secondary mt-4"><span>✓ Fleksibel, dari mana saja</span><span>✓ Terbuka untuk semua</span><span>✓ Transaksi tercatat</span></div>
    </div>
    <div class="col-12 col-lg-6 text-center">
      <a href="{{ asset('assets/img/nugasin-partner.png') }}" target="_blank" rel="noopener"><img class="img-fluid rounded-4 shadow-sm marketing-poster" src="{{ asset('assets/img/nugasin-partner.png') }}" alt="Poster Nugasin Partner: komisi 10 persen, bonus referral, daftar layanan, dan cara bergabung" width="1024" height="1536"></a>
      <a href="{{ asset('assets/img/nugasin-partner.png') }}" download class="d-inline-block mt-3 small">Unduh poster untuk dibagikan</a>
    </div>
  </div></section>
  <section class="bg-body-tertiary border-top border-bottom py-5"><div class="container"><div class="row g-4">
    <div class="col-12 col-lg-5"><div class="card border-0 rounded-4 h-100 shadow-sm"><div class="card-body p-4 p-md-5">
      <h2 class="h4 fw-bold">Komisi Langsung</h2><div class="display-2 fw-bold text-primary my-3">10%</div>
      <p class="fs-5">Dari setiap transaksi berhasil yang kamu rekomendasikan.</p>
      <p class="text-secondary mb-0">Bagikan kode referral kamu. Komisi dan bonus yang sudah dibukukan dapat dipantau melalui Cuanin.</p>
    </div></div></div>
    <div class="col-12 col-lg-7"><div class="card border-0 rounded-4 h-100 shadow-sm"><div class="card-body p-4">
      <h2 class="h4 fw-bold">Bonus Referral</h2><p class="text-secondary">Lebih banyak teman, lebih banyak peluang!</p>
      <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Referral Berhasil</th><th class="text-end">Bonus</th></tr></thead><tbody>
      @foreach(config('marketing.bonuses') as $count => $amount)<tr><td>{{ $count }} orang</td><td class="text-end fw-bold text-primary">Rp{{ number_format($amount, 0, ',', '.') }}</td></tr>@endforeach
      </tbody></table></div>
      <p class="small text-secondary mb-0">Bonus merupakan total kumulatif seumur akun, dihitung dari client unik dengan pembayaran yang disetujui admin. Contoh: 10 client memperoleh Rp50.000; pada 20 client bertambah Rp50.000 sehingga total bonus Rp100.000. Bonus didanai dari pemasukan setelah komisi; jika belum cukup, sisanya menunggu pemasukan berikutnya dan terlihat di Cuanin.</p>
    </div></div></div>
  </div></div></section>
  <section class="container py-5"><div class="row g-5">
    <div class="col-12 col-lg-6"><h2 class="h3 fw-bold mb-4">Daftar Layanan Nugasin</h2>
      <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Layanan</th><th class="text-end">Harga</th></tr></thead><tbody>
      @foreach(config('marketing.services') as [$name, $price])<tr><td>{{ $name }}</td><td class="text-end text-nowrap">Rp{{ number_format($price, 0, ',', '.') }}</td></tr>@endforeach
      </tbody></table></div>
    </div>
    <div class="col-12 col-lg-6"><h2 class="h3 fw-bold mb-4">Cara Mudah Bergabung</h2>
      <ol class="list-group list-group-numbered list-group-flush">
        @foreach(['Daftar jadi Nugasin Partner.', 'Buat kode referral kamu.', 'Bagikan kode ke teman-teman kamu.', 'Teman order dengan kode kamu dan melakukan pembayaran.', 'Pantau komisi dan bonus melalui Cuanin.'] as $step)<li class="list-group-item border-0 py-3">{{ $step }}</li>@endforeach
      </ol>
    </div>
  </div></section>
  <section class="container pb-5"><div class="row g-3 mb-4">
    @foreach(['Transaksi tercatat dengan jelas', 'Fleksibel dikerjakan dari mana saja', 'Untuk mahasiswa, komunitas, dan siapa saja yang punya jaringan', 'Penghasilan tambahan dari rekomendasi kamu'] as $benefit)<div class="col-12 col-md-6 col-lg-3"><div class="border rounded-4 p-4 h-100 bg-body-tertiary">✓ {{ $benefit }}</div></div>@endforeach
  </div><div class="text-center bg-primary-subtle rounded-4 p-4 p-md-5">
    <h2 class="h3 fw-bold">Yuk, jadi Nugasin Partner sekarang!</h2><p class="text-secondary">Kuliah lebih ringan, masa depan lebih cerah bersama Nugasin Class.</p>
    <a class="btn btn-primary btn-lg rounded-pill px-4" href="{{ route('marketing.register') }}">Daftar sebagai Marketing →</a>
  </div></section>
</main>
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('assets/css/marketing.css') }}">@endpush
