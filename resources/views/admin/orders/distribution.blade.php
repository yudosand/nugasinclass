<div class="card border-0 shadow-sm rounded-4 mb-3"><div class="card-body p-4"><h2 class="h5 fw-bold">Rincian Pembagian Cuanin</h2>
@if(!$cuaninReady)<p>Aktifkan Cuanin sebelum menyetujui pembayaran agar pembagian tercatat.</p><a class="btn btn-outline-primary rounded-pill" href="{{ route('cuanin.index') }}">Buka Cuanin</a>
@elseif($distribution)
<div class="table-responsive"><table class="table"><tbody>
<tr><th>Total Harga</th><td class="text-end">{{ \App\Support\Cuanin::format($distribution->gross) }}</td></tr>
<tr><td>Komisi Referral {{ $distribution->referral_code ? '('.$distribution->referral_code.')' : '(tidak ada)' }}</td><td class="text-end">{{ \App\Support\Cuanin::format($distribution->commission) }}</td></tr>
<tr><td>Dana Bonus Marketing</td><td class="text-end">{{ \App\Support\Cuanin::format($distribution->bonus) }}</td></tr>
<tr><th>Total Dibagi ke {{ $distribution->admin_count }} Admin</th><td class="text-end fw-bold">{{ \App\Support\Cuanin::format($distribution->admin_pool) }}</td></tr>
</tbody></table><table class="table align-middle"><thead><tr><th>Penerima</th><th>Keterangan</th><th class="text-end">Nominal</th></tr></thead><tbody>
@foreach($distribution->allocations as $allocation)<tr><td>{{ $allocation['name'] }}</td><td>{{ $allocation['kind'] }}</td><td class="text-end">{{ \App\Support\Cuanin::format($allocation['amount']) }}</td></tr>@endforeach
</tbody></table></div><p class="small text-secondary mb-0">Dibukukan {{ \App\Support\Cuanin::date($distribution->created_at) }}. Rincian ini tetap mengikuti jumlah admin saat transaksi disetujui. Jika nominal tidak habis dibagi rata, sisa rupiah diputar antar admin pada transaksi berikutnya.</p>
@elseif($order->payment_status === 'approved')<p class="text-secondary mb-0">Transaksi telah disetujui sebelum pembukuan Cuanin. Tidak dibagikan ulang secara otomatis.</p>
@else<p>Kode referral: <strong>{{ $referral?->code ?? 'Tidak ada' }}</strong>. {{ $referral ? 'Komisi referral 10% akan dipotong dari total.' : 'Tidak ada komisi referral.' }}</p><p class="text-secondary mb-0">Pembagian final dibukukan saat Approve: komisi, pendanaan bonus yang tertunda, kemudian sisa untuk admin aktif.</p>@endif
</div></div>
