<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  @include('partials.favicon')
  <title>@yield('title', 'Nugasin Class - Solusi Akademikmu')</title>

  @unless(request()->routeIs('marketing.register', 'marketing.login'))
  <script>
    (function () {
      try {
        var t = localStorage.getItem('nc_theme');
        if (!t) {
          t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        document.documentElement.setAttribute('data-bs-theme', t);
      } catch (e) {}
    })();
  </script>
  @endunless

  @unless(request()->routeIs('marketing.register', 'marketing.login'))
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  @endunless
  <link href="{{ asset('assets/css/bootstrap-5.3.3.min.css') }}" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}" />
  @stack('styles')
</head>

<body>
  <nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom sticky-top">
    <div class="container py-1">
      <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('landing.index') }}">
        <img src="{{ asset('assets/img/logo-nugasin-class.png') }}" alt="Logo Nugasin Class" />
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navMain">
        <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
          <a class="nav-link {{ request()->routeIs('landing.index') ? 'active' : '' }}" href="{{ route('landing.index') }}">Beranda</a>
          <a class="nav-link {{ request()->routeIs('landing.pricelist') ? 'active' : '' }}" href="{{ route('landing.pricelist') }}">Daftar Harga</a>
          <a class="nav-link {{ request()->routeIs('landing.testimoni') ? 'active' : '' }}" href="{{ route('landing.testimoni') }}">Testimoni</a>
          <a class="nav-link" href="{{ route('marketing.index') }}">Jadi Partner</a>

          <div class="d-lg-none my-2"></div>
          <button id="btnTheme" class="btn btn-outline-secondary btn-sm rounded-pill px-3" type="button" aria-label="Toggle theme">🌙</button>

          @auth
            @if(auth()->user()->isMarketing())
              <a class="btn btn-primary rounded-pill" href="{{ route('cuanin.index') }}">Cuanin</a>
            @elseif(auth()->user()->isStudent())
              @if(request()->routeIs('exams.*','landing.simulasi') || (request()->routeIs('payment.*') && isset($isExam) && $isExam))
              <a class="btn btn-primary rounded-pill" href="{{ route('exams.mine') }}">Paket UAS Saya</a>
              @else
              <a class="btn btn-primary rounded-pill px-3 ms-lg-2" href="{{ route('study.dashboard') }}">Dashboard Tracker</a>
              @endif
            @elseif(auth()->user()->isAdmin())
              <a class="btn btn-primary rounded-pill px-3 ms-lg-2" href="{{ route('admin.dashboard') }}">Backoffice Admin</a>
            @else
              <a class="btn btn-primary rounded-pill px-3 ms-lg-2" href="{{ route('client.dashboard') }}">Dashboard Client</a>
            @endif
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 ms-lg-1">Logout</button>
            </form>
          @else
            <a class="btn btn-outline-primary rounded-pill px-3 ms-lg-2" href="{{ route('login') }}">Masuk</a>
            <a class="btn btn-primary rounded-pill px-3" href="{{ route('register') }}">Daftar</a>
          @endauth
        </div>
      </div>
    </div>
  </nav>

  <div class="container mt-3">
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if(session('warning'))
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if(session('info'))
      <div class="alert alert-info alert-dismissible fade show" role="alert">
        {{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif
  </div>

  @yield('content')

  <footer class="footer">
    <div class="container py-4 d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
      <div class="text-secondary">© <span data-year></span> Nugasin Class. All Rights Reserved.</div>
      <div class="d-flex flex-wrap gap-2">
        <a class="text-secondary text-decoration-none" href="{{ route('landing.pricelist') }}">Daftar Harga</a>
        <span class="text-secondary">•</span>
        <a class="text-secondary text-decoration-none" href="{{ route('landing.testimoni') }}">Testimoni</a>
        <span class="text-secondary">•</span>
        <a class="text-secondary text-decoration-none" href="{{ route('marketing.index') }}">Jadi Partner</a>
        <span class="text-secondary">•</span>
        <a class="text-secondary text-decoration-none" href="{{ route('terms') }}">Syarat &amp; Ketentuan</a>
      </div>
    </div>
  </footer>

  <script src="{{ asset('assets/js/bootstrap-5.3.3.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/app.js') }}?v=20261029-2" defer></script>
  @stack('scripts')
</body>
</html>
