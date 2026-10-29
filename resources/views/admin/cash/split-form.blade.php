@if($order->cashAccount)
<div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
<details @if($errors->hasAny(['split','admin_ids','split_reason'])) open @endif><summary class="fw-bold text-primary">Ubah Admin Penerima Pembagian</summary>
<p class="mt-3">Pilih admin sesuai kesepakatan khusus order ini. Bagian admin dari pembayaran yang sudah diterima dihitung ulang dan pembayaran berikutnya mengikuti pilihan baru. Komisi referral dan bonus tetap.</p>
<form method="POST" action="{{ route('admin.orders.split.preview', $order->id) }}">@csrf
<fieldset class="mb-3"><legend class="h6">Admin penerima</legend>
@foreach($splitAdmins as $member)<div class="form-check mb-2"><input type="checkbox" class="form-check-input" id="split_admin_{{ $member->id }}" name="admin_ids[]" value="{{ $member->id }}" @checked(in_array($member->id, old('admin_ids', $order->cashAccount->admin_ids)))><label class="form-check-label" for="split_admin_{{ $member->id }}">{{ $member->name }}</label></div>@endforeach
</fieldset>
<label class="form-label" for="split_reason">Alasan / kesepakatan pembagian</label><textarea class="form-control mb-3" id="split_reason" name="split_reason" minlength="5" maxlength="2000" required>{{ old('split_reason') }}</textarea>
<button class="btn btn-outline-primary rounded-pill">Pratinjau Pembagian Baru</button>
</form></details>
</div></div>
@endif
@if($splitChanges->isNotEmpty())
<div class="card border-0 shadow-sm mb-4"><div class="card-body p-4"><h2 class="h5 fw-bold">Riwayat Koreksi Pembagian</h2>
@foreach($splitChanges as $change)
@php($snapshot = json_decode($change->snapshot, true))
<details class="border-top py-3"><summary>{{ \App\Support\Cuanin::date($change->created_at) }} · {{ $change->actor_name }}</summary>
<p class="mt-2">{{ $change->reason }}</p><div class="table-responsive"><table class="table"><thead><tr><th>Admin</th><th>Bagian lama</th><th>Bagian baru</th><th>Koreksi Cuanin</th></tr></thead><tbody>
@foreach($snapshot['balances'] as $balance)<tr><td>{{ $balance['name'] }}</td><td>{{ \App\Support\Cuanin::format($balance['old_share']) }}</td><td>{{ \App\Support\Cuanin::format($balance['new_share']) }}</td><td>{{ \App\Support\Cuanin::format($balance['delta']) }}</td></tr>@endforeach
</tbody></table></div></details>
@endforeach
</div></div>
@endif
