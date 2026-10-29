<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Akses E-Learning - Nugasin Class')</title>
  @include('partials.favicon')
  <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-5.3.3.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body class="bg-body-tertiary">
  <nav class="border-bottom bg-body py-3">
    <div class="container d-flex flex-wrap align-items-center justify-content-between gap-3">
      <a class="navbar-brand" href="{{ route('landing.index') }}"><img src="{{ asset('assets/img/logo-nugasin-class.png') }}" alt="Nugasin Class"></a>
      <div class="d-flex gap-3 align-items-center">
        <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('client.dashboard') }}">Kembali ke Dashboard</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-danger btn-sm rounded-pill">Logout</button></form>
      </div>
    </div>
  </nav>
  @yield('content')
  @stack('scripts')
</body>
</html>
