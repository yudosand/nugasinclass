@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.marketing')
@section('title', 'Aktifkan Cuanin - Nugasin Class')
@section('content')
<div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><h1 class="h3 fw-bold">Cuanin</h1><p>Dompet pembagian transaksi dan komisi referral.</p>@include('partials.form-errors')
@if(auth()->user()->isAdmin())
<h2 class="h5">Aturan pembukuan</h2><ul>
<li>Saldo dibukukan sekali setelah pembayaran disetujui admin. Transaksi lama yang sudah approved tidak dibagikan ulang.</li>
<li>Komisi referral 10% dari harga transaksi, dibulatkan ke bawah ke Rupiah.</li>
<li>Bonus marketing merupakan total target per tingkat: 10 client Rp50.000; 20 Rp100.000; 30 Rp200.000; 40 Rp300.000; 50 Rp500.000; 75 Rp750.000; 100 Rp1.000.000. Yang dihitung adalah client unik dengan pembayaran disetujui, tanpa reset bulanan.</li>
<li>Setelah komisi, dana dialokasikan untuk bonus yang sudah menjadi hak marketing. Kekurangan bonus menunggu dana transaksi berikutnya, dengan urutan bonus terlama.</li>
<li>Sisa transaksi dibagi rata ke seluruh admin berstatus approved saat approval. Sisa pembulatan Rp1 dialokasikan menurut urutan ID admin. Bonus yang sedang didanai dapat mengurangi bagian admin hingga nol.</li>
<li>Pencairan menahan seluruh saldo tersedia. OUT dibukukan setelah admin mencatat transfer nyata selesai; penolakan melepaskan saldo yang ditahan.</li>
</ul>
<form method="POST" action="{{ route('cuanin.activate') }}">@csrf
<div class="form-check mb-3"><input class="form-check-input" id="rules_accepted" type="checkbox" name="rules_accepted" value="1" required><label class="form-check-label" for="rules_accepted">Saya sudah meninjau dan menggunakan aturan di atas.</label></div>
<label class="form-label" for="current_password">Password admin Anda</label><input type="password" class="form-control mb-3" id="current_password" name="current_password" required autocomplete="current-password">
<button class="btn btn-primary rounded-pill">Aktifkan Cuanin & Marketing</button></form>
@else<p class="alert alert-info">Admin perlu mengaktifkan Cuanin terlebih dahulu.</p>@endif
</div></div>
@endsection
