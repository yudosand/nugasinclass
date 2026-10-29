@extends('layouts.exam')
@section('title','Hasil Ujian - '.$catalog->title)
@section('content')
@php($scored=count(array_filter($questions,fn($q)=>!empty($q['answer']))))
<div class="card rounded-4 p-4 mb-4"><h1 class="h4 fw-bold">Hasil Ujian: {{ $catalog->title }}</h1>
@if($scored)<p class="display-5 fw-bold text-primary">{{ round($attempt->correct / $scored*100,1) }}</p><p>{{ $attempt->correct }} benar dari {{ $scored }} soal dengan kunci terverifikasi. Jawaban kosong pada soal tersebut dihitung salah.</p>@else<p>Latihan selesai. Jawaban tersimpan untuk tinjauan mandiri; paket ini belum memiliki kunci yang dapat dinilai otomatis.</p>@endif
@if($scored<count($questions))<p>{{ count($questions)-$scored }} soal uraian/kunci belum terverifikasi tidak dimasukkan ke perhitungan nilai.</p>@endif
<p class="small text-secondary">Penilaian mengikuti kunci materi sumber, bukan jaminan nilai UAS resmi. Sebagian sumber memakai kunci hasil analisis.</p><a href="{{ route('exams.learn',$catalog->id) }}">Buka materi belajar dan jawaban yang tersedia</a></div>
<div class="table-responsive"><table class="table bg-white"><thead><tr><th>Soal</th><th>Jawaban Anda</th><th>Kunci sumber</th><th>Hasil</th></tr></thead><tbody>@foreach($questions as $i=>$q)<tr><td>{{ $i+1 }}<br><small>{{ $q['number']??'' }} · Hal. {{ $q['page']??'—' }}</small></td><td class="exam-written-answer">{{ $answers[$i]??'—' }}</td><td>{{ $q['answer']??'Tinjauan mandiri' }}</td><td>{{ empty($q['answer'])?'Tidak dinilai otomatis':(($answers[$i]??null)===$q['answer']?'Benar':'Salah / kosong') }}</td></tr>@endforeach</tbody></table></div>
@endsection
