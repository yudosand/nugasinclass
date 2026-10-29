@extends('layouts.exam')
@section('title','Ujian - '.$catalog->title)
@section('content')
<div class="d-flex flex-wrap justify-content-between gap-3 mb-4"><div><h1 class="h4 fw-bold">{{ $catalog->title }}</h1><span>Soal {{ $index+1 }} dari {{ count($questions) }} · {{ count($answers) }} terjawab</span></div><div class="border rounded-3 p-3 bg-white">Sisa waktu <strong id="exam-timer" data-seconds="{{ max(0, now()->diffInSeconds(\Carbon\Carbon::parse($attempt->ends_at), false)) }}">—</strong></div></div>
<div class="row g-4"><aside class="col-lg-3"><div class="card p-3 rounded-4"><h2 class="h6 fw-bold">Navigasi Soal</h2><div class="d-flex flex-wrap gap-2">@foreach($questions as $i=>$q)<a class="btn btn-sm {{ $i===$index?'btn-primary':(isset($answers[$i])?'btn-success':'btn-outline-secondary') }}" href="{{ route('exams.attempt',['id'=>$attempt->id,'q'=>$i]) }}">{{ $i+1 }}</a>@endforeach</div><p class="small text-secondary mt-3 mb-0">Simpan jawaban sebelum berpindah melalui nomor soal.</p></div></aside>
<section class="col-lg-9"><div class="card p-3 p-md-4 rounded-4"><div class="exam-protected mb-3">@if(!empty($questions[$index]["prompt"]))<p class="exam-written-answer">{{ $questions[$index]["prompt"] }}</p>@else<img class="exam-page" src="{{ route('exams.question',['id'=>$attempt->id,'index'=>$index]) }}" alt="Soal {{ $index+1 }}" draggable="false">@endif</div>
<form method="POST" action="{{ route('exams.answer',$attempt->id) }}" id="exam-answer-form">@csrf<input type="hidden" name="index" value="{{ $index }}"><input type="hidden" name="next" value="{{ min($index+1,count($questions)-1) }}">
@if(($questions[$index]['type']??'choice')==='essay')
<label class="form-label fw-bold" for="essay-answer">Jawaban uraian</label><p class="small text-secondary">Jawab nomor-nomor pada lembar ini. Jawaban disimpan untuk ditinjau sendiri dan tidak diberi nilai otomatis.</p><textarea id="essay-answer" class="form-control mb-4" name="answer" rows="10" maxlength="20000">{{ $answers[$index]??'' }}</textarea>
@else
<fieldset><legend class="h6">Pilih jawaban</legend><div class="d-grid gap-2 mb-4">@foreach($questions[$index]['options'] as $option)<label class="border rounded-3 p-3"><input class="form-check-input me-2" type="radio" name="answer" value="{{ $option }}" @checked(($answers[$index]??null)===$option)> <strong>{{ $option }}.</strong> {{ $questions[$index]['choices'][$option]??'' }}</label>@endforeach</div></fieldset>
@endif
<div class="d-flex flex-wrap gap-2"><button class="btn btn-primary rounded-pill">Simpan & Lanjut</button><button class="btn btn-outline-danger rounded-pill" name="finish" value="1" data-confirm-finish>Selesai Ujian</button></div></form></div></section></div>
@endsection
