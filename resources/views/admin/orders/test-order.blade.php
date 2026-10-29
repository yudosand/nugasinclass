@if(\App\Support\Exams::isOrder($order->id))
@php($testOrder=\App\Support\ExamTestOrders::get($order->id))
<div class="card border-warning mb-4"><div class="card-body p-4">
  <h2 class="h5 fw-bold">{{ $testOrder ? 'Transaksi Uji Simulasi UAS' : 'Uji Alur Pembelian' }}</h2>
  @if($testOrder)
    <p class="mb-2"><span class="badge bg-warning text-dark">{{ $order->payment_label }}</span></p>
    <p>Ditandai oleh {{ $testOrder->actor_name }} · {{ \App\Support\Cuanin::date($testOrder->created_at) }}. {{ $testOrder->reason }}</p>
    <p>Tidak ada uang masuk, tagihan riil, atau pembagian Cuanin dari order ini. Harga paket tetap tercatat sebagai acuan.</p>
    @if($testOrder->approved_at)<p>Nominal simulasi: <strong>{{ \App\Support\Cuanin::format($testOrder->simulated_amount) }}</strong> · Disetujui {{ \App\Support\Cuanin::date($testOrder->approved_at) }}.</p>@endif
    @if(!$testOrder->revoked_at)
      <form method="POST" action="{{ route('admin.orders.test',$order->id) }}" class="row g-3">@csrf
        <input type="hidden" name="action" value="{{ $testOrder->approved_at ? 'revoke' : 'approve' }}">
        @if(!$testOrder->approved_at)
          <p class="mb-0">Peserta upload bukti contoh, lalu kirim konfirmasi WhatsApp bertanda UJI. Setelah itu, setujui akses di sini.</p>
          <div class="col-md-6"><label for="simulated_amount" class="form-label">Nominal simulasi (Rp), bukan uang diterima</label><input id="simulated_amount" name="simulated_amount" type="number" min="1" max="{{ (int)$order->amount }}" value="{{ old('simulated_amount',1000) }}" class="form-control" required></div>
        @endif
        <div class="col-md-6"><label for="test_password" class="form-label">Password admin Anda</label><input id="test_password" name="current_password" type="password" autocomplete="current-password" class="form-control" required></div>
        <div class="col-12 form-check ms-2"><input id="test_confirm" name="confirmed" value="1" type="checkbox" class="form-check-input" required><label for="test_confirm" class="form-check-label">{{ $testOrder->approved_at ? 'Cabut akses peserta untuk order uji ini.' : 'Ini hanya pengujian, tidak ada pembayaran nyata.' }}</label></div>
        <div class="col-12"><button class="btn {{ $testOrder->approved_at ? 'btn-outline-danger' : 'btn-primary' }} rounded-pill">{{ $testOrder->approved_at ? 'Cabut Akses Uji' : 'Setujui Akses Uji' }}</button></div>
      </form>
    @endif
  @else
    <p>Untuk mencoba daftar → pilih paket → upload bukti contoh → WhatsApp → persetujuan admin → belajar dan ujian. Tandai order sebelum mengunggah bukti contoh. Jangan isi pembayaran nyata jika tidak ada transfer.</p>
    <details><summary class="text-primary fw-semibold">Tandai Sebagai Transaksi Uji</summary>
      <form method="POST" action="{{ route('admin.orders.test',$order->id) }}" class="row g-3 mt-1">@csrf<input type="hidden" name="action" value="mark">
        <div class="col-md-6"><label for="test_reason" class="form-label">Alasan pengujian</label><input id="test_reason" name="reason" value="{{ old('reason','Pengujian alur pembelian Simulasi UAS') }}" minlength="5" maxlength="1000" class="form-control" required></div>
        <div class="col-md-6"><label for="mark_test_password" class="form-label">Password admin Anda</label><input id="mark_test_password" type="password" name="current_password" autocomplete="current-password" class="form-control" required></div>
        <div class="col-12 form-check ms-2"><input id="mark_test_confirm" name="confirmed" type="checkbox" value="1" class="form-check-input" required><label for="mark_test_confirm" class="form-check-label">Order ini untuk tes dan tidak menerima pembayaran nyata. Penandaan ini tidak dapat dibatalkan.</label></div>
        <div class="col-12"><button class="btn btn-outline-primary rounded-pill">Tandai Transaksi Uji</button></div>
      </form>
    </details>
  @endif
</div></div>
@endif
