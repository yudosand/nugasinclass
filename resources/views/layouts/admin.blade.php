<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  @include('partials.favicon')
  <title>@yield('title', 'Admin Backoffice - Nugasin Class')</title>

  @unless(request()->routeIs('cuanin.*', 'admin.cuanin.*'))
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

  @unless(request()->routeIs('cuanin.*', 'admin.cuanin.*'))
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  @endunless
  <link href="{{ asset('assets/css/bootstrap-5.3.3.min.css') }}" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}" />
  @stack('styles')
</head>

<body class="bg-body-tertiary">
  <nav class="navbar navbar-expand-lg bg-body border-bottom sticky-top shadow-sm">
    <div class="container-fluid px-lg-4">
      <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('admin.dashboard') }}">
        <img src="{{ asset('assets/img/logo-nugasin-class.png') }}" alt="Logo Nugasin Class" height="34" />
        <span class="badge bg-primary rounded-pill">Admin Backoffice</span>
      </a>

      <div class="ms-auto d-flex align-items-center gap-3">
        <button id="btnTheme" class="btn btn-outline-secondary btn-sm rounded-pill px-3" type="button">🌙</button>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle px-3" type="button" data-bs-toggle="dropdown">
            👤 {{ auth()->user()->name }}
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ route('landing.index') }}" target="_blank">🌐 Lihat Website</a></li>
            <li><a class="dropdown-item" href="{{ route('password.edit') }}">Ubah Password</a></li>
            <li><a class="dropdown-item" href="{{ route('admin.profile.edit') }}">Profil Saya / Ubah Nama</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="dropdown-item text-danger">Logout</button>
              </form>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <div class="container-fluid px-lg-4 py-4">
    <div class="row g-4">
      <!-- Admin Sidebar -->
      <div class="col-12 col-md-3 col-lg-2">
        <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 80px; z-index: 10;">
          <div class="card-body p-3">
            <div class="text-uppercase small text-secondary fw-semibold mb-2 px-2">Menu Utama</div>
            <div class="nav flex-column nav-pills gap-1">
              <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : 'text-body' }}" href="{{ route('admin.dashboard') }}">
                📊 Dashboard
              </a>
              <a class="nav-link {{ request()->routeIs('admin.clients*') ? 'active' : 'text-body' }}" href="{{ route('admin.clients.index') }}">
                👥 Clients
              </a>
              <a class="nav-link {{ request()->routeIs('admin.admins*') ? 'active' : 'text-body' }}" href="{{ route('admin.admins.index') }}">🛡️ Daftar Admin</a>
              <a class="nav-link {{ request()->routeIs('admin.marketing.*') ? 'active' : 'text-body' }}" href="{{ route('admin.marketing.index') }}">🤝 Daftar Marketing</a>
              <a class="nav-link {{ request()->routeIs('admin.study-users.*') ? 'active' : 'text-body' }}" href="{{ route('admin.study-users.index') }}">🧑‍🎓 Daftar Pengguna</a>
              <a class="nav-link {{ request()->routeIs('admin.exams.index') ? 'active' : 'text-body' }}" href="{{ route('admin.exams.index') }}">📖 Paket Simulasi UAS</a>
              <a class="nav-link {{ request()->routeIs('admin.exams.clients') ? 'active' : 'text-body' }}" href="{{ route('admin.exams.clients') }}">🎓 Client Simulasi UAS</a>
              <a class="nav-link {{ request()->routeIs('admin.chat*') ? 'active' : 'text-body' }}" href="{{ route('admin.chat.index') }}">💬 Chat Admin</a>
              <a class="nav-link {{ request()->routeIs('admin.profile*') ? 'active' : 'text-body' }}" href="{{ route('admin.profile.edit') }}">👤 Profil Saya</a>
              <a class="nav-link {{ request()->routeIs('cuanin.*', 'admin.cuanin.*') ? 'active' : 'text-body' }}" href="{{ route('cuanin.index') }}">💰 Cuanin</a>
              <a class="nav-link {{ request()->routeIs('admin.orders*') ? 'active' : 'text-body' }}" href="{{ route('admin.orders.index') }}">
                📦 Orders
              </a>
              <a class="nav-link {{ request()->fullUrlIs('*status=paid*') ? 'active' : 'text-body' }}" href="{{ route('admin.orders.index', ['status' => 'paid']) }}">
                💳 Payments
              </a>
              <a class="nav-link {{ request()->routeIs('admin.packages*') ? 'active' : 'text-body' }}" href="{{ route('admin.packages.index') }}">
                🏷️ Packages
              </a>
              <a class="nav-link {{ request()->routeIs('admin.academic*') ? 'active' : 'text-body' }}" href="{{ route('admin.academic.index') }}">
                📚 Academic Progress
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="col-12 col-md-9 col-lg-10">
        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show rounded-3 mb-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        @yield('content')
      </div>
    </div>
  </div>

  <script src="{{ asset('assets/js/bootstrap-5.3.3.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/app.js') }}?v=20261029-2" defer></script>
  @stack('scripts')
</body>
</html>
