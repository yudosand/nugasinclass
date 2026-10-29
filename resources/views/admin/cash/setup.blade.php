@extends('layouts.admin')
@section('title', 'Cocokkan Kas Awal - Nugasin Class')
@section('content')
<a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary rounded-pill mb-3">Kembali ke Orders</a>
<h1 class="h3 fw-bold">Cocokkan Kas Awal</h1>
@include('partials.form-errors')
@if(!$ready)
<div class="card border-0 shadow-sm"><div class="card-body p-4"><p>Cocokkan uang yang benar-benar diterima dan total pencairan tim sebelum menggunakan pembayaran bertahap. Siapkan catatan pembayaran dan backup database. Saldo belum diubah sampai pratinjau disetujui.</p>
<form method="POST" action="{{ route('admin.cash.prepare') }}">@csrf<label class="form-label" for="prepare_password">Password admin Anda</label><input id="prepare_password" class="form-control mb-3" type="password" name="current_password" autocomplete="current-password" required><button class="btn btn-primary rounded-pill">Mulai Pencocokan Kas</button></form></div></div>
@else
<div class="alert alert-info">Isi nominal uang yang benar-benar sudah diterima untuk setiap order, termasuk order yang sebelumnya keliru dianggap lunas. Isi 0 jika belum dibayar. Total pencairan adalah jumlah kumulatif yang sudah ditransfer, termasuk pencairan yang sudah ada di sistem.</div>
<form method="POST" action="{{ route('admin.cash.preview') }}">@csrf
<div class="card border-0 shadow-sm mb-4"><div class="card-body p-4"><h2 class="h5">Pembayaran Client</h2><div class="table-responsive"><table class="table"><thead><tr><th>Order / Client</th><th>Total Harga</th><th>Uang Sudah Diterima (Rp)</th></tr></thead><tbody>
@foreach($sources as $source)<tr><td>{{ $source['order_number'] }}<div class="small">{{ $source['client_name'] }}</div></td><td>{{ \App\Support\Cuanin::format($source['total']) }}</td><td><input aria-label="Diterima {{ $source['order_number'] }}" name="received[{{ $source['order_id'] }}]" class="form-control" type="number" min="0" max="{{ $source['total'] }}" step="1" value="{{ old('received.'.$source['order_id']) }}" required></td></tr>@endforeach
</tbody></table></div>@if(!$sources)<input name="received" type="hidden" value=""><p>Belum ada order.</p>@endif</div></div>
<div class="card border-0 shadow-sm mb-4"><div class="card-body p-4"><h2 class="h5">Total Pencairan Selesai per Orang</h2><p>Jangan menjumlahkan ulang transaksi yang sudah tercatat. Contoh: sudah tercatat Rp300.000 dan tambahan transfer manual Rp250.000, isi total Rp550.000.</p>
@foreach($members as $member)<div class="row mb-3 align-items-center"><label for="paid_{{ $member->id }}" class="col-md-6">{{ $member->name }} <span class="small text-secondary">(sistem: {{ \App\Support\Cuanin::format($knownPaid[$member->id] ?? 0) }})</span></label><div class="col-md-6"><input id="paid_{{ $member->id }}" class="form-control" name="withdrawn[{{ $member->id }}]" type="number" min="{{ $knownPaid[$member->id] ?? 0 }}" step="1" value="{{ old('withdrawn.'.$member->id, $knownPaid[$member->id] ?? 0) }}" required></div></div>@endforeach
</div></div><label for="opening_note" class="form-label">Catatan bendahara (opsional)</label><textarea id="opening_note" class="form-control mb-3" name="note" maxlength="2000">{{ old('note') }}</textarea><button class="btn btn-primary rounded-pill">Lihat Pratinjau, Belum Simpan</button></form>
@endif
@endsection
