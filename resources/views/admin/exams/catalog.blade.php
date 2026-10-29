@extends('layouts.admin')
@section('title','Paket Simulasi UAS - Admin')
@section('content')
<div class="d-flex flex-wrap justify-content-between gap-3 mb-4"><div><h1 class="h3 fw-bold">Paket Simulasi UAS</h1><p class="text-secondary">Atur harga, waktu ujian, dan paket yang tampil untuk pembeli.</p></div><form method="POST" action="{{ route('admin.exams.import') }}">@csrf<button class="btn btn-outline-primary rounded-pill">Impor Bank Soal Bawaan</button></form></div>
@include('partials.form-errors')
<div class="alert alert-info">Paket baru dibuat sebagai draft dengan harga awal Rp15.000. Periksa materi dan harga sebelum mengaktifkan. Harga order lama tetap tersimpan.</div>
@if($catalog->isNotEmpty())
<div class="d-flex flex-wrap align-items-center gap-3 mb-3"><button type="submit" form="save-all-packages" class="btn btn-primary rounded-pill">Simpan Semua Paket</button><a href="{{ route('exams.mine') }}" class="btn btn-outline-primary rounded-pill">Demo & Riwayat Ujian Saya</a><span class="small text-secondary">Simpan perubahan sebelum membuka demo atau koreksi soal.</span></div>
<form id="save-all-packages" method="POST" action="{{ route('admin.exams.update-all') }}">@csrf @method('PUT')
@endif
<div class="row g-3">@forelse($catalog as $item)<div class="col-xl-6"><div class="card p-4 rounded-4 h-100"><h2 class="h5 fw-bold">{{ $item->title }}</h2><p class="small text-secondary">{{ $item->code }} · {{ array_sum(array_column(json_decode($item->documents,true),'pages')) }} halaman · {{ $counts[$item->id] }} soal interaktif · {{ $scored[$item->id] }} dengan kunci · {{ $counts[$item->id]-$scored[$item->id] }} tinjauan mandiri</p><p><a href="{{ route('admin.exams.preview',$item->id) }}" target="_blank" rel="noopener">Pratinjau materi halaman pertama</a> · <a href="{{ route('admin.exams.questions',$item->id) }}">Tinjau / Koreksi Soal</a></p>
<input type="hidden" name="packages[{{ $item->id }}][revision]" value="{{ old('packages.'.$item->id.'.revision', \App\Http\Controllers\Admin\ExamManagementController::revision($item)) }}"><div class="row g-3">
<div class="col-12"><label class="form-label" for="price{{ $item->id }}">Harga (Rp)</label><input class="form-control" id="price{{ $item->id }}" name="packages[{{ $item->id }}][price]" type="number" min="1000" max="100000000" step="1" value="{{ old('packages.'.$item->id.'.price',(int)$item->price) }}" required></div>
<div class="col-6"><label class="form-label" for="duration{{ $item->id }}">Durasi (menit)</label><input class="form-control" id="duration{{ $item->id }}" name="packages[{{ $item->id }}][duration_minutes]" type="number" min="5" max="240" value="{{ old('packages.'.$item->id.'.duration_minutes',$item->duration_minutes) }}" required></div>
<div class="col-6"><label class="form-label" for="count{{ $item->id }}">Soal per ujian</label><input class="form-control" id="count{{ $item->id }}" name="packages[{{ $item->id }}][questions_per_attempt]" type="number" min="1" max="100" value="{{ old('packages.'.$item->id.'.questions_per_attempt',$item->questions_per_attempt) }}" required></div>
<div class="col-12"><label class="form-label" for="published{{ $item->id }}">Status penjualan</label><select class="form-select" name="packages[{{ $item->id }}][published]" id="published{{ $item->id }}"><option value="0" @selected(!old('packages.'.$item->id.'.published',$item->published))>Draft / tidak dijual</option><option value="1" @selected(old('packages.'.$item->id.'.published',$item->published))>Aktif / dijual</option></select></div>
<div class="col-12 d-flex flex-wrap gap-2"><button type="submit" form="demo-learn-{{ $item->id }}" class="btn btn-outline-primary rounded-pill">Demo Belajar</button><button type="submit" form="demo-exam-{{ $item->id }}" class="btn btn-outline-primary rounded-pill">Demo Ujian</button></div></div></div></div>@empty<p>Belum ada paket. Klik Impor Bank Soal Bawaan untuk menyiapkan paket dari materi yang disertakan.</p>@endforelse</div>
@if($catalog->isNotEmpty())<button class="btn btn-primary rounded-pill mt-4">Simpan Semua Paket</button></form>
@foreach($catalog as $item)
<form id="demo-learn-{{ $item->id }}" method="POST" action="{{ route('exams.open',$item->id) }}">@csrf</form>
<form id="demo-exam-{{ $item->id }}" method="POST" action="{{ route('exams.start',$item->id) }}">@csrf</form>
@endforeach
@endif
@endsection
