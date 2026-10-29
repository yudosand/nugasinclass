<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Nugasin Partner')</title>@include('partials.favicon')
<link rel="stylesheet" href="{{ asset('assets/css/bootstrap-5.3.3.min.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head><body class="bg-body-tertiary">
<nav class="bg-body border-bottom py-3"><div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
<a class="navbar-brand" href="{{ route('marketing.index') }}"><img src="{{ asset('assets/img/logo-nugasin-class.png') }}" alt="Nugasin Partner"></a>
<div class="d-flex flex-wrap align-items-center gap-3"><span>{{ auth()->user()->name }}</span><a class="btn btn-primary rounded-pill" href="{{ route('cuanin.index') }}">Cuanin</a><a class="btn btn-outline-primary rounded-pill" href="{{ route('marketing.clients') }}">Client</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-danger rounded-pill">Logout</button></form></div>
</div></nav><main class="container py-4">
@foreach(['success', 'error'] as $notice)
@if(session($notice))<div class="alert {{ $notice === 'success' ? 'alert-success' : 'alert-danger' }}">{{ session($notice) }}</div>@endif
@endforeach
@yield('content')</main>@stack('scripts')</body></html>
