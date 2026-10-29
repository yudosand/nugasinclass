@extends('layouts.admin')
@section('title','Tinjau Soal Simulasi UAS')
@section('content')
<a href="{{ route('admin.exams.index') }}" class="btn btn-outline-secondary mb-3">← Paket Simulasi UAS</a>
<h1 class="h4 fw-bold">{{ $catalog->title }} — Soal {{ $index+1 }} / {{ $total }}</h1>
@include('partials.form-errors')
<p class="text-secondary">Periksa teks pilihan dan kunci hasil impor terhadap materi sumber. Kosongkan kunci jika belum terverifikasi; soal tersebut tidak dinilai otomatis.</p>
<div class="d-flex gap-2 mb-3">@if($index>0)<a class="btn btn-outline-primary" href="{{ route('admin.exams.questions',['id'=>$catalog->id,'q'=>$index-1]) }}">Sebelumnya</a>@endif @if($index+1<$total)<a class="btn btn-outline-primary" href="{{ route('admin.exams.questions',['id'=>$catalog->id,'q'=>$index+1]) }}">Selanjutnya</a>@endif</div>
<div class="row g-4"><div class="col-lg-7"><div class="card p-3"><img class="img-fluid" src="{{ route('admin.exams.questions.image',['id'=>$catalog->id,'index'=>$index]) }}" alt="Soal"><p class="small">Sumber: {{ $question['source'] }}, halaman {{ $question['page'] }}.</p><a href="{{ route('admin.exams.preview',['id'=>$catalog->id,'page'=>$question['study_page']??$question['page']]) }}" target="_blank" rel="noopener">Lihat halaman asli beserta kunci yang tersedia</a></div></div>
<div class="col-lg-5"><form method="POST" action="{{ route('admin.exams.questions.save',['id'=>$catalog->id,'index'=>$index]) }}" class="card p-4">@csrf @method('PUT')<input type="hidden" name="revision" value="{{ hash('sha256',json_encode($question)) }}">
<label class="form-label" for="prompt">Teks soal pengganti (opsional)</label><textarea id="prompt" name="prompt" class="form-control mb-3" rows="5">{{ old('prompt',$question['prompt']??'') }}</textarea><p class="small text-secondary">Kosongkan untuk memakai gambar soal. Isi jika perlu memperbaiki atau mengetik ulang soal.</p>
@if(($question['type']??'choice')!=='essay')@foreach($question['options'] as $option)<label class="form-label" for="choice{{ $option }}">Pilihan {{ $option }}</label><textarea id="choice{{ $option }}" name="choices[{{ $option }}]" class="form-control mb-3" rows="2" required>{{ old('choices.'.$option,$question['choices'][$option]??'') }}</textarea>@endforeach
<label for="key" class="form-label">Kunci jawaban</label><select id="key" name="answer" class="form-select mb-3"><option value="">Belum terverifikasi</option>@foreach($question['options'] as $option)<option value="{{ $option }}" @selected(old('answer',$question['answer'])===$option)>{{ $option }}</option>@endforeach</select>@endif
<label class="form-label" for="enabled">Status soal</label><select name="enabled" id="enabled" class="form-select mb-3"><option value="1" @selected($question['enabled']??true)>Aktif</option><option value="0" @selected(!($question['enabled']??true))>Nonaktif</option></select><button class="btn btn-primary">Simpan Koreksi Soal</button></form></div></div>
@endsection
