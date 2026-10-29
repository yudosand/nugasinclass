@extends('layouts.exam')
@section('title','Belajar - '.$catalog->title)
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3"><div><span class="text-primary small">Mode Belajar</span><h1 class="h4 fw-bold">{{ $catalog->title }}</h1><p class="small text-secondary mb-0">Soal dan jawaban mengikuti materi sumber. Akses pribadi dengan watermark akun.</p></div><form method="POST" action="{{ route('exams.start',$catalog->id) }}">@csrf<button class="btn btn-primary rounded-pill">Mulai Ujian</button></form></div>
<nav class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3" aria-label="Halaman materi">
@if($page>1)<a class="btn btn-outline-primary" href="{{ route('exams.learn',['id'=>$catalog->id,'page'=>$page-1]) }}">← Sebelumnya</a>@else<span></span>@endif
<form method="GET" action="{{ route('exams.learn',$catalog->id) }}" class="d-flex gap-2 align-items-center"><label for="study-page">Halaman</label><input class="form-control" id="study-page" name="page" type="number" min="1" max="{{ $pages }}" value="{{ $page }}"><span>/ {{ $pages }}</span><button class="btn btn-outline-primary">Buka</button></form>
@if($page<$pages)<a class="btn btn-outline-primary" href="{{ route('exams.learn',['id'=>$catalog->id,'page'=>$page+1]) }}">Selanjutnya →</a>@else<span></span>@endif</nav>
<div class="exam-protected"><img class="exam-page" src="{{ route('exams.page',['id'=>$catalog->id,'page'=>$page]) }}" alt="Materi {{ $catalog->title }}, halaman {{ $page }}" draggable="false"></div>
@endsection
