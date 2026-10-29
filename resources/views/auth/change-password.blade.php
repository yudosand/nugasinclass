@extends(auth()->user()->isMarketing() ? 'layouts.marketing' : 'layouts.app')
@section('title', 'Ubah Password - Nugasin Class')
@section('content')
<main class="container py-5"><div class="row justify-content-center"><div class="col-12 col-md-8 col-lg-6">
<div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4 p-md-5">
  <h1 class="h3 fw-bold">Ubah Password</h1>
  <p class="text-secondary">Gunakan password baru minimal 12 karakter. Setelah disimpan, Anda akan diminta login kembali.</p>
  @include('partials.form-errors')
  <form method="POST" action="{{ route('password.update') }}">@csrf @method('PUT')
    <label class="form-label" for="current_password">Password lama</label><input class="form-control mb-3" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
    <label class="form-label" for="password">Password baru</label><input class="form-control mb-3" id="password" name="password" type="password" minlength="12" maxlength="72" autocomplete="new-password" required>
    <label class="form-label" for="password_confirmation">Ulangi password baru</label><input class="form-control mb-4" id="password_confirmation" name="password_confirmation" type="password" minlength="12" maxlength="72" autocomplete="new-password" required>
    <button class="btn btn-primary rounded-pill px-4" type="submit">Simpan Password Baru</button>
  </form>
</div></div></div></div></main>
@endsection
