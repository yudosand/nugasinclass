@extends('layouts.admin')
@section('title', 'Pratinjau Koreksi Pembagian')
@section('content')
<h1 class="h3 fw-bold">Pratinjau Koreksi Pembagian</h1>
<p>Order {{ $plan['order_number'] }}. Belum ada saldo yang diubah.</p>
@include('partials.form-errors')
@if(!empty($plan['negative_exception']))<div class="alert alert-warning"><strong>Pengecualian sekali untuk order Laila.</strong> Saldo Indra akan menjadi {{ \App\Support\Cuanin::format($plan['balances'][14]['after']) }} sebagai utang penyesuaian. Cuan berikutnya otomatis menutup kekurangan. Indra tidak bisa mencairkan sampai saldo tersedia positif. Kas riil tidak berubah.</div>@endif
<div class="alert alert-info">Pembayaran diterima: <strong>{{ \App\Support\Cuanin::format($plan['received']) }}</strong>. Koreksi hanya memindahkan bagian antar-admin; total kas, pembayaran, dan sisa tagihan tetap.</div>
<div class="card mb-4"><div class="card-body table-responsive"><table class="table"><thead><tr><th>Admin</th><th>Bagian order lama</th><th>Bagian order baru</th><th>Koreksi saldo</th><th>Saldo Cuanin lama</th><th>Saldo Cuanin baru</th></tr></thead><tbody>
@foreach($plan['balances'] as $balance)<tr><td>{{ $balance['name'] }}</td>@foreach(['old_share','new_share','delta','before','after'] as $field)<td>{{ \App\Support\Cuanin::format($balance[$field]) }}</td>@endforeach</tr>@endforeach
</tbody></table><p class="small text-secondary">Saldo Cuanin mencakup seluruh order, termasuk saldo yang ditahan untuk pencairan. Nominal ditahan tidak berubah. Riwayat pembagian lama disimpan bersama alasan koreksi.</p></div></div>
<p><strong>Alasan:</strong> {{ $draft['reason'] }}</p>
<form method="POST" action="{{ route('admin.orders.split.commit', $plan['order_id']) }}">@csrf
<input type="hidden" name="draft_key" value="{{ $draft['key'] }}">
@if(!empty($plan['negative_exception']))<div class="form-check mb-3"><input id="confirmed_laila_debt" class="form-check-input" type="checkbox" name="confirmed_laila_debt" value="1" required><label for="confirmed_laila_debt" class="form-check-label">Saya menyetujui saldo minus Indra khusus koreksi order Laila ini, maksimal Rp50.001.</label></div>@endif
<div class="form-check mb-3"><input id="split_confirmed" class="form-check-input" type="checkbox" name="confirmed" value="1" required><label for="split_confirmed" class="form-check-label">Pembagian ini sudah disepakati, termasuk koreksi pembayaran sebelumnya dan penerima pembayaran berikutnya.</label></div>
<label for="split_password" class="form-label">Password admin Anda</label><input id="split_password" name="current_password" type="password" autocomplete="current-password" class="form-control mb-3" required>
<button class="btn btn-primary rounded-pill">Simpan Koreksi Pembagian</button> <a class="btn btn-outline-secondary rounded-pill" href="{{ route('admin.orders.show', $plan['order_id']) }}">Kembali ke Order</a>
</form>
@endsection
