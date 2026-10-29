@extends('layouts.app')

@section('title', 'Konfirmasi & Pembayaran Order - Nugasin Class')

@section('content')
@php($isExam = \App\Support\Exams::isOrder($order->id))
@php($testOrder = \App\Support\ExamTestOrders::get($order->id))
<main class="container py-5">
  @if($isExam)<div class="text-center mb-3"><a href="{{ route('exams.mine') }}">← Paket Simulasi UAS Saya</a><p class="text-secondary small mt-2">{{ $testOrder ? 'Transaksi uji: akses dibuka setelah admin menyetujui bukti contoh.' : 'Akses soal terbuka setelah pembayaran penuh diverifikasi admin.' }}</p></div>@endif
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 p-md-5">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
              <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-2">Order #{{ $order->order_number }}</span>
              <h1 class="h3 fw-bold mb-1">Pembayaran Paket Layanan</h1>
              <p class="text-secondary mb-0">{{ $testOrder ? 'Upload bukti contoh dan konfirmasi pengujian melalui WhatsApp.' : 'Bayar melalui QRIS, upload bukti pembayaran, lalu konfirmasi melalui WhatsApp.' }}</p>
            </div>
            <div>
              @if($testOrder)
                <span class="badge bg-warning text-dark fs-6 rounded-pill px-3 py-2">{{ $order->payment_label }}</span>
              @elseif($order->received_amount !== null)
                <span class="badge bg-primary fs-6 rounded-pill px-3 py-2">{{ $order->payment_label }}</span>
              @elseif($order->payment_status === 'pending')
                <span class="badge bg-warning text-dark fs-6 rounded-pill px-3 py-2">Menunggu Pembayaran</span>
              @elseif($order->payment_status === 'paid')
                <span class="badge bg-info text-dark fs-6 rounded-pill px-3 py-2">Menunggu Konfirmasi Admin</span>
              @elseif($order->payment_status === 'approved')
                <span class="badge bg-success fs-6 rounded-pill px-3 py-2">✓ Pembayaran Approved</span>
              @elseif($order->payment_status === 'rejected')
                <span class="badge bg-danger fs-6 rounded-pill px-3 py-2">✕ Ditolak</span>
              @endif
            </div>
          </div>

          @if($testOrder)<div class="alert alert-warning"><strong>TRANSAKSI UJI</strong> — Tidak perlu transfer uang. Upload gambar/PDF contoh bertuliskan UJI, lalu konfirmasi ke admin. Order ini tidak menambah kas atau Cuanin.<div>{{ $order->payment_label }}</div></div>@endif
          <div class="row g-4 mb-4">
            <!-- Detail Order -->
            <div class="col-12 col-md-6">
              <div class="bg-body-tertiary p-4 rounded-4 border h-100">
                <h5 class="fw-bold mb-3">Detail Layanan</h5>
                <div class="mb-2 text-secondary">Pemesan: <strong class="text-body">{{ $order->user->name }}</strong> ({{ $order->user->nomor_hp }})</div>
                <div class="mb-2 text-secondary">Paket: <strong class="text-primary">{{ $order->package->name }}</strong></div>
                <div class="display-6 fw-bold text-primary my-3">
                  Rp {{ number_format($order->amount, 0, ',', '.') }}
                </div>
                <p class="small text-secondary mb-0">{{ $order->package->description }}</p>
                @if(!$testOrder && $order->received_amount !== null)<hr><div>Sudah dibayar: <strong>{{ \App\Support\Cuanin::format($order->received_amount) }}</strong></div><div>Sisa tagihan: <strong>{{ \App\Support\Cuanin::format($order->outstanding_amount) }}</strong></div>@endif
              </div>
            </div>

            @if(!$testOrder)
            <!-- QRIS asli dari pemilik usaha -->
            <div class="col-12 col-md-6">
              <div class="bg-body-tertiary p-4 rounded-4 border h-100">
                <h5 class="fw-bold mb-3">Pembayaran QRIS</h5>
                <div class="p-2 bg-white rounded-3 border text-center mb-3">
                  <a href="{{ asset('assets/img/qris-nugasin-class.png') }}" target="_blank" rel="noopener noreferrer" aria-label="Buka QRIS Nugasin Class ukuran penuh">
                    <img src="{{ asset('assets/img/qris-nugasin-class.png') }}" alt="QRIS pembayaran Nugasin Class" class="img-fluid rounded-2" width="1136" height="1600" style="max-width: 320px; width: 100%; height: auto;">
                  </a>
                </div>
                <a href="{{ asset('assets/img/qris-nugasin-class.png') }}" download="QRIS-Nugasin-Class.png" class="btn btn-outline-primary rounded-pill w-100 mb-3">Unduh QRIS</a>
                <p class="small text-secondary mb-2">Scan QRIS dengan aplikasi bank atau e-wallet. Jika memakai satu HP, unduh gambar lalu pilih dari galeri pada aplikasi pembayaran.</p>
                <p class="small mb-0">Pastikan nama penerima <strong>Nugasin Class</strong>. {{ $order->received_amount !== null ? 'Sisa tagihan' : 'Total tagihan' }}: <strong>Rp {{ number_format($order->received_amount !== null ? $order->outstanding_amount : $order->amount, 0, ',', '.') }}</strong>. Pembayaran bertahap mengikuti kesepakatan dengan admin.</p>
              </div>
            </div>
          @endif
          </div>
          <hr class="my-4">

          <!-- Form Upload Bukti Pembayaran -->
          <div class="p-4 border rounded-4 bg-body-tertiary">
            <h5 class="fw-bold mb-2">Upload Bukti Pembayaran</h5>
            <p class="text-secondary small mb-3">{{ $testOrder ? 'Upload bukti contoh bertuliskan UJI (JPG, PNG atau PDF, maksimal 5 MB). Tidak perlu transfer.' : 'Setelah membayar melalui QRIS, upload bukti pembayaran (JPG, PNG, JPEG, PDF, maksimal 5 MB).' }}</p>

            @error('payment_proof')
              <div class="alert alert-danger" role="alert">{{ $message }}</div>
            @enderror

            @if($order->payment_proof && $order->payment_status === 'paid')
              <div class="alert alert-success rounded-3 mb-4">
                <h6 class="fw-bold">Bukti pembayaran sudah diterima</h6>
                <p class="small mb-3">Lanjutkan konfirmasi melalui WhatsApp agar admin dapat memeriksa pembayaran Anda.</p>
                <a href="{{ 'https://wa.me/6285861015161?text=' . rawurlencode($testOrder ? 'Halo Kak, ini PENGUJIAN Simulasi UAS, TIDAK ADA TRANSFER UANG. Order: '.$order->order_number.'. Nama: '.$order->user->name.'. Paket: '.$order->package->name.'. Bukti contoh sudah diunggah. Mohon Setujui Akses Uji melalui backoffice.' : ($isExam ? 'Halo Kak, saya ingin konfirmasi pembayaran Simulasi UAS. Order: '.$order->order_number.'. Nama: '.$order->user->name.'. Paket: '.$order->package->name.'. Total tagihan: Rp '.number_format($order->amount,0,',','.').'. Bukti pembayaran sudah saya unggah. Mohon diperiksa. Terima kasih.' : 'Halo Kak, saya sudah melakukan pembayaran ya. Mohon dibantu cek dan konfirmasi pembayarannya. Terima kasih 🙏')) }}" target="_blank" rel="noopener noreferrer" class="btn btn-success rounded-pill px-4 fw-bold">Konfirmasi Pembayaran via WhatsApp</a>
                <p class="small mt-2 mb-0">Pesan sudah disiapkan. Tekan kirim di WhatsApp untuk menghubungi admin.</p>
              </div>
            @endif

            @if($order->payment_proof)
              <div class="mb-3">
                <div class="small text-success fw-semibold mb-2">✓ Bukti Pembayaran Telah Di-upload:</div>
                @if(Str::endsWith(strtolower($order->payment_proof), '.pdf'))
                  <a href="{{ route('payment.proof', $order->id) }}" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill">📄 Lihat Dokumen PDF</a>
                @else
                  <img src="{{ route('payment.proof', $order->id) }}" alt="Bukti Pembayaran" class="img-fluid rounded-3 border shadow-sm style-preview" style="max-height: 250px;">
                @endif
              </div>
            @endif

            @if($order->payment_status !== 'approved')
              <form action="{{ route('payment.upload', ['order' => $order->id]) }}" method="POST" enctype="multipart/form-data" data-proof-upload>
                @csrf
                <div class="mb-3">
                  <input type="file" class="form-control rounded-3" id="payment_proof" name="payment_proof" aria-label="Pilih bukti pembayaran" accept="image/jpeg,image/png,application/pdf" required>
                </div>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                  {{ $order->payment_proof ? 'Upload Ulang Bukti Pembayaran' : 'Kirim Bukti Pembayaran' }}
                </button>
                <div class="mt-3 small" role="status" aria-live="polite" data-upload-status></div>
              </form>
            @else
              <div class="alert alert-success rounded-3 mb-0">
                @if($isExam)
                  {{ $testOrder ? $order->payment_label.'.' : 'Pembayaran Anda sudah disetujui.' }} <a href="{{ route('exams.mine') }}" class="fw-bold text-success">Buka Paket Simulasi UAS Saya</a>.
                @else
                  Pembayaran Anda sudah disetujui. Silakan <a href="{{ route('onboarding.show') }}" class="fw-bold text-success">Lengkapi Data Onboarding</a> untuk mengakses Dashboard Client.
                @endif
              </div>
            @endif
          </div>

        </div>
      </div>
    </div>
  </div>
</main>
@endsection
@push('scripts')
<script src="{{ asset('assets/js/payment-upload.js') }}" defer></script>
@endpush
