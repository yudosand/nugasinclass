@extends('layouts.app')
@section('title', 'Lupa Password - Nugasin Class')
@section('content')
<main class="container py-5"><div class="row justify-content-center"><div class="col-12 col-md-8 col-lg-6">
  <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4 p-md-5">
    <h1 class="h3 fw-bold">Lupa password?</h1>
    <p class="text-secondary">Untuk akun admin, client, marketing, dan pengguna Study Tracker. Hubungi admin melalui WhatsApp. Admin akan memverifikasi kepemilikan akun lalu mengirim tautan untuk membuat password baru.</p>
    <ol class="ps-3"><li>Sampaikan nama, jenis akun, serta nomor HP atau email yang terdaftar.</li><li>Ikuti verifikasi identitas dari admin. Jangan membagikan password atau OTP. Jika Anda admin, minta bantuan rekan admin yang masih bisa login.</li><li>Buka tautan reset dari admin. Tautan berlaku {{ config('auth.passwords.users.expire', 60) }} menit dan hanya dapat digunakan sekali.</li></ol>
    <a class="btn btn-success rounded-pill px-4" href="{{ 'https://wa.me/6285861015161?text='.rawurlencode('Halo Kak, saya lupa password akun Nugasin Class. Mohon bantu verifikasi akun dan kirim tautan reset password. Terima kasih 🙏') }}" target="_blank" rel="noopener noreferrer">Minta Reset via WhatsApp</a>
    <p class="mt-4 mb-0"><a href="{{ route('login') }}">← Kembali ke login</a></p>
  </div></div>
</div></div></main>
@endsection
