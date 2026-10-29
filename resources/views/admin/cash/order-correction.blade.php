<div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
<details @if($errors->hasAny(['correction', 'new_total', 'package_id', 'reason'])) open @endif>
<summary class="fw-bold text-primary">Koreksi Paket / Harga Order</summary>
<p class="mt-3">Gunakan jika paket atau harga kesepakatan berubah. Koreksi memperbarui sisa tagihan; pembayaran yang sudah tercatat tetap dipertahankan.</p>
<form method="POST" action="{{ route('admin.orders.correction', $order->id) }}" class="row g-3">
@csrf
<input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
<input type="hidden" name="expected_total" value="{{ (int) $order->amount }}">
<input type="hidden" name="expected_package_id" value="{{ $order->package_id }}">
<div class="col-md-6"><label for="correction_package" class="form-label">Paket yang benar</label>
<select id="correction_package" name="package_id" class="form-select" required>
@foreach($packages as $package)<option value="{{ $package->id }}" @selected((string) old('package_id', $order->package_id) === (string) $package->id)>{{ $package->name }} · {{ \App\Support\Cuanin::format((int) $package->price) }}</option>@endforeach
</select></div>
<div class="col-md-6"><label for="correction_total" class="form-label">Total harga baru (Rp)</label>
<input id="correction_total" name="new_total" type="text" inputmode="numeric" class="form-control" value="{{ old('new_total', number_format((int) $order->amount, 0, ',', '.')) }}" required>
<div class="form-text">Isi harga kesepakatan, bukan nominal pembayaran. Sudah diterima: {{ \App\Support\Cuanin::format($order->received_amount) }}.</div></div>
<div class="col-12"><label for="correction_reason" class="form-label">Alasan koreksi</label><textarea id="correction_reason" name="reason" class="form-control" minlength="5" maxlength="2000" placeholder="Contoh: layanan berubah dari Full SI menjadi All Prodi, harga disepakati Rp500.000." required>{{ old('reason') }}</textarea></div>
<div class="col-md-6"><label for="correction_password" class="form-label">Password admin Anda</label><input id="correction_password" name="current_password" type="password" autocomplete="current-password" class="form-control" required></div>
<div class="col-12"><div class="form-check"><input id="correction_confirmed" name="confirmed" value="1" type="checkbox" class="form-check-input" required><label for="correction_confirmed" class="form-check-label">Paket dan harga baru sudah sesuai kesepakatan dengan client.</label></div></div>
<div class="col-12"><button class="btn btn-outline-primary rounded-pill">Simpan Koreksi Order</button></div>
</form></details></div></div>
