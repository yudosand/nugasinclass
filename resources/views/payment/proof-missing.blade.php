@extends('layouts.app')
@section('title', 'Bukti Pembayaran Belum Ditemukan - Nugasin Class')
@section('content')
<main class="container py-5"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
  <h1 class="h4 fw-bold">File bukti pembayaran belum ditemukan</h1>
  <p>File untuk pesanan {{ $order->order_number }} tidak tersedia atau belum dapat dibaca.</p>
  @if(auth()->user()->isAdmin())
    <p>Periksa folder penyimpanan bukti di File Manager. Jika file lama hilang saat update, pulihkan dari backup atau minta client mengunggah ulang.</p>
  @else
    <p>Hubungi admin untuk memeriksa file yang tersimpan atau unggah kembali bukti pembayaran Anda.</p>
  @endif
  <a class="btn btn-primary rounded-pill" href="{{ auth()->user()->isAdmin() ? route('admin.orders.show', $order->id) : route('payment.show', $order->id) }}">Kembali ke Pesanan</a>
</div></div></main>
@endsection
