@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.marketing')
@section('title', 'Pengajuan Pencairan - Cuanin')
@section('content')
<a class="btn btn-outline-secondary rounded-pill mb-3" href="{{ route('cuanin.index') }}">← Cuanin</a>@include('partials.form-errors')
<div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><h1 class="h3 fw-bold">Pengajuan #CUAN-{{ $withdrawal->id }}</h1>
<p>{{ $withdrawal->wallet->user->name }} • {{ \App\Support\Cuanin::date($withdrawal->created_at) }}</p><div class="h2 text-primary fw-bold">{{ \App\Support\Cuanin::format($withdrawal->amount) }}</div>
<p>Rekening tujuan: <strong>{{ $bank['bank_name'] }} / {{ $bank['account_number'] }} / a.n. {{ $bank['account_name'] }}</strong></p><p>Status: <strong>{{ ['pending'=>'Menunggu proses', 'paid'=>'Selesai', 'rejected'=>'Ditolak'][$withdrawal->status] }}</strong></p>
@if($withdrawal->status === 'pending')<a class="btn btn-success rounded-pill" href="{{ $whatsapp }}" rel="noopener noreferrer" target="_blank">Kirim Permintaan via WhatsApp</a><p class="small text-secondary mt-2">Pesan berisi nominal, bank, nama, dan nomor rekening tujuan. Klik Kirim di WhatsApp untuk menyampaikannya ke admin.</p>@endif
@if($withdrawal->payment_reference)<p>Catatan proses: {{ $withdrawal->payment_reference }}</p>@endif
@if(auth()->user()->isAdmin() && $withdrawal->status === 'pending')<hr><h2 class="h5 fw-bold">Proses Pengajuan</h2><form method="POST" action="{{ route('admin.cuanin.process', $withdrawal->id) }}">@csrf
<label for="action" class="form-label">Hasil</label><select id="action" name="action" class="form-select mb-3"><option value="paid">Transfer sudah selesai</option><option value="rejected">Tolak dan kembalikan saldo tersedia</option></select>
<label for="payment_reference" class="form-label">Referensi transfer / alasan penolakan</label><input id="payment_reference" name="payment_reference" class="form-control mb-3" maxlength="255" required>
<label for="current_password" class="form-label">Password admin Anda</label><input id="current_password" name="current_password" type="password" class="form-control mb-3" required>
<div class="form-check mb-3"><input class="form-check-input" name="confirmed" id="confirmed" type="checkbox" value="1" required><label for="confirmed" class="form-check-label">Saya sudah memeriksa pengajuan. Jika memilih selesai, transfer bank benar-benar telah dilakukan.</label></div>
<button class="btn btn-primary rounded-pill">Catat Hasil Pencairan</button></form>@endif
</div></div>
@endsection
