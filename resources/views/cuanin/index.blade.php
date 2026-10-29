@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.marketing')
@section('title', 'Cuanin - Nugasin Class')
@section('content')
<div class="d-flex flex-wrap justify-content-between gap-3 mb-4"><div><h1 class="h3 fw-bold mb-1">Cuanin</h1><p class="text-secondary mb-0">Cuan saya, rekening, dan riwayat transaksi.</p></div>@if(auth()->user()->isAdmin())<a class="btn btn-outline-primary rounded-pill align-self-start" href="{{ route('admin.cuanin.requests') }}">Kelola Pengajuan Pencairan</a>@endif</div>
@include('partials.form-errors')
@if($wallet->balance < 0)<div class="alert alert-warning"><strong>Utang penyesuaian: {{ \App\Support\Cuanin::format(-$wallet->balance) }}.</strong> Cuan yang masuk berikutnya otomatis mengurangi kekurangan ini. Pencairan baru tersedia setelah saldo positif.</div>@endif
@if(\App\Support\CashSchema::installed() && !\App\Support\CashSchema::active())<div class="alert alert-warning">Saldo sedang dicocokkan dengan pembayaran nyata. Pengajuan pencairan baru dibuka setelah pencocokan kas awal selesai.</div>@endif
<div class="row g-3 mb-4"><div class="col-12 col-md-4"><div class="card border-0 bg-primary text-white rounded-4 h-100"><div class="card-body p-4"><div>Cuan Saya • Tersedia</div><div class="h2 fw-bold mt-2">{{ \App\Support\Cuanin::format($wallet->available()) }}</div></div></div></div>
<div class="col-12 col-md-4"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4"><div class="text-secondary">Ditahan untuk pencairan</div><div class="h3 fw-bold mt-2">{{ \App\Support\Cuanin::format($wallet->reserved) }}</div></div></div></div>
<div class="col-12 col-md-4"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4"><div class="text-secondary">Client referral berhasil</div><div class="h3 fw-bold mt-2">{{ $conversions }} client</div>@if(auth()->user()->isMarketing())<div class="small">Bonus belum didanai: {{ \App\Support\Cuanin::format($pendingBonus) }}</div>@endif</div></div></div></div>
<div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4"><h2 class="h5 fw-bold">Kode Referral Saya: <span class="text-primary">{{ $code }}</span></h2><p class="text-secondary mb-2">Bagikan tautan ini agar kode otomatis terisi pada pendaftaran client.</p><input class="form-control" readonly aria-label="Tautan referral" value="{{ rtrim(config('app.url'), '/').route('register', ['ref' => $code], false) }}"></div></div>
<div class="row g-4 mb-4"><div class="col-12 col-lg-6"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4"><h2 class="h5 fw-bold">Data Rekening</h2>
@if($wallet->account_number)<p>{{ $wallet->bank_name }} • {{ $wallet->account_name }}<br>Nomor rekening: ••••{{ substr($wallet->account_number, -4) }}</p>@else<p class="text-secondary">Lengkapi rekening sebelum mengajukan pencairan.</p>@endif
<form method="POST" action="{{ route('cuanin.bank') }}">@csrf @method('PUT')
<label for="bank_name" class="form-label">Nama Bank</label><input id="bank_name" name="bank_name" value="{{ old('bank_name') }}" class="form-control mb-3" maxlength="100" required>
<label for="account_name" class="form-label">Nama Pemilik Rekening</label><input id="account_name" name="account_name" value="{{ old('account_name') }}" class="form-control mb-3" maxlength="255" required>
<label for="account_number" class="form-label">Nomor Rekening</label><input id="account_number" name="account_number" class="form-control mb-3" inputmode="numeric" pattern="[0-9]{5,30}" maxlength="30" autocomplete="off" required>
<label for="bank_password" class="form-label">Password akun Anda</label><input id="bank_password" name="current_password" class="form-control mb-3" type="password" autocomplete="current-password" required>
<button class="btn btn-outline-primary rounded-pill">Simpan Rekening</button></form>
</div></div></div><div class="col-12 col-lg-6"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4"><h2 class="h5 fw-bold">Pencairan Cuan Saya</h2>
@if($pending)<div class="alert alert-warning">Pengajuan #CUAN-{{ $pending->id }} sebesar {{ \App\Support\Cuanin::format($pending->amount) }} sedang diproses.</div><a href="{{ route('cuanin.withdrawal', $pending->id) }}" class="btn btn-success rounded-pill">Konfirmasi Pencairan via WhatsApp</a>
@else<p>Masukkan nominal yang ingin dicairkan. Nominal tidak boleh melebihi saldo tersedia dan akan ditahan setelah pengajuan dibuat.</p>
<form method="POST" action="{{ route('cuanin.withdraw') }}">@csrf<input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
<label for="withdraw_amount" class="form-label">Nominal pencairan</label>
<div class="input-group mb-2"><span class="input-group-text" id="withdraw_currency">Rp</span><input id="withdraw_amount" name="amount" type="text" class="form-control" inputmode="numeric" autocomplete="off" data-max="{{ max(0, $wallet->available()) }}" aria-describedby="withdraw_currency withdraw_balance" value="{{ old('amount', $wallet->available() > 0 ? number_format($wallet->available(), 0, ',', '.') : '') }}" required></div>
<div id="withdraw_balance" class="small text-secondary mb-3">Saldo tersedia: {{ \App\Support\Cuanin::format($wallet->available()) }}</div>
<label for="withdraw_password" class="form-label">Password akun Anda</label><input id="withdraw_password" name="current_password" type="password" class="form-control mb-3" autocomplete="current-password" required>
<button class="btn btn-primary rounded-pill" @disabled($wallet->available() <= 0 || !$wallet->account_number)>Ajukan Pencairan</button></form>@endif
<p class="small text-secondary mt-3">Pencairan diselesaikan setelah transfer bank dikonfirmasi. WhatsApp tidak mentransfer uang secara otomatis.</p>
</div></div></div></div>
<div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4"><h2 class="h5 fw-bold">History IN / OUT</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Waktu</th><th>Jenis</th><th>Keterangan</th><th class="text-end">Nominal</th></tr></thead><tbody>
@forelse($entries as $entry)<tr><td>{{ \App\Support\Cuanin::date($entry->created_at) }}</td><td><span class="badge {{ $entry->amount > 0 ? 'bg-success' : 'bg-danger' }}">{{ $entry->amount > 0 ? 'IN' : 'OUT' }}</span></td><td>{{ $entry->description }}</td><td class="text-end text-nowrap">{{ \App\Support\Cuanin::format(abs($entry->amount)) }}</td></tr>@empty<tr><td colspan="4" class="text-secondary text-center py-4">Belum ada transaksi Cuanin.</td></tr>@endforelse
</tbody></table></div>{{ $entries->withQueryString()->links() }}</div></div>
<div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><h2 class="h5 fw-bold">Riwayat Pengajuan Pencairan</h2><div class="table-responsive"><table class="table"><thead><tr><th>Pengajuan</th><th>Nominal</th><th>Status</th></tr></thead><tbody>
@forelse($withdrawals as $withdrawal)<tr><td><a href="{{ route('cuanin.withdrawal', $withdrawal->id) }}">#CUAN-{{ $withdrawal->id }}</a></td><td>{{ \App\Support\Cuanin::format($withdrawal->amount) }}</td><td>{{ ['pending'=>'Diajukan', 'paid'=>'Selesai', 'rejected'=>'Ditolak'][$withdrawal->status] }}</td></tr>@empty<tr><td colspan="3" class="text-secondary">Belum ada pengajuan.</td></tr>@endforelse
</tbody></table></div>{{ $withdrawals->withQueryString()->links() }}</div></div>
@endsection
@push('scripts')
<script src="{{ asset('assets/js/cuanin-amount.js') }}" defer></script>
@endpush
