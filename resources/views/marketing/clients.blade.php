@extends('layouts.marketing')
@section('title', 'Client Referral Saya - Nugasin Partner')
@section('content')
<h1 class="h3 fw-bold">Client Referral Saya</h1><p class="text-secondary">Client yang mendaftar menggunakan kode referral Anda.</p>
<div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Client</th><th>Order</th><th>Paket</th><th>Total</th><th>Status Pembayaran</th></tr></thead><tbody>
@forelse($clients as $client)<tr><td>{{ $client->name }}</td><td>{{ $client->order_number }}</td><td>{{ $client->package_name }}</td><td>Rp{{ number_format($client->amount, 0, ',', '.') }}@isset($client->received)<div class="small">Diterima: Rp{{ number_format($client->received, 0, ',', '.') }}<br>Sisa: Rp{{ number_format($client->amount - $client->received, 0, ',', '.') }}</div>@endisset</td><td>@if(isset($client->received) && $client->received > 0){{ $client->received >= $client->amount ? 'Lunas' : 'Dibayar Sebagian' }}@else{{ ['approved'=>'Disetujui', 'paid'=>'Menunggu verifikasi', 'pending'=>'Belum dibayar', 'rejected'=>'Ditolak'][$client->payment_status] ?? $client->payment_status }}@endif</td></tr>
@empty<tr><td colspan="5" class="text-center py-4 text-secondary">Belum ada client referral. Bagikan kode dari menu Cuanin.</td></tr>@endforelse
</tbody></table></div>{{ $clients->links() }}</div></div>
@endsection
