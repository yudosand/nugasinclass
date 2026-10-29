@extends('layouts.app')

@section('title', 'Dashboard Client - Nugasin Class')

@section('content')
<main class="container py-5">
  <!-- Profile & Package Banner -->
  <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body">
    <div class="card-body p-4 p-md-5">
      <div class="row align-items-center g-4">
        <div class="col-12 col-md-8">
          <div class="d-flex align-items-center gap-3 mb-2">
            <span class="fs-1">🎓</span>
            <div>
              <h2 class="fw-bold mb-0">Selamat Datang, {{ $user->name }}</h2>
              <div class="text-secondary small">{{ $user->profile->universitas }} • {{ $user->profile->program_studi }} (Semester {{ $user->profile->semester }})</div>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-3 mt-3">
            <div class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
              NIM: {{ $user->profile->nim }}
            </div>
            <div class="badge bg-success-subtle text-success rounded-pill px-3 py-2">
              Paket: {{ $latestOrder && $latestOrder->package ? $latestOrder->package->name : 'Aktif' }}
            </div>
            <div class="badge bg-info-subtle text-info rounded-pill px-3 py-2">
              Status Akun: Approved
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4 text-md-end">
          <a href="{{ route('onboarding.show') }}" class="btn btn-outline-secondary btn-sm rounded-pill">✏️ Edit Data Akademik</a>
          <a href="{{ route('password.edit') }}" class="btn btn-outline-primary btn-sm rounded-pill mt-2">Ubah Password</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Progress Sesi 1 - 8 Section -->
  @if(\App\Support\CashSchema::active())
    @foreach($user->orders as $clientOrder)
      @if($clientOrder->outstanding_amount > 0)<div class="alert alert-info">Order {{ $clientOrder->order_number }}: sudah dibayar {{ \App\Support\Cuanin::format($clientOrder->received_amount) }}, sisa tagihan <strong>{{ \App\Support\Cuanin::format($clientOrder->outstanding_amount) }}</strong>. <a href="{{ route('payment.show', $clientOrder->id) }}">Pembayaran / Upload Bukti Pelunasan</a></div>@endif
    @endforeach
  @endif
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="fw-bold mb-1">Progress Akademik Nugasin</h3>
      <p class="text-secondary mb-0">Pantau status pengerjaan Diskusi dan Tugas untuk Sesi 1 sampai Sesi 8.</p>
    </div>
  </div>

  @if($user->courses->isEmpty())
    <div class="card border-0 shadow-sm rounded-4 text-center p-5">
      <div class="fs-1 mb-2">📚</div>
      <h4 class="fw-bold">Belum Ada Mata Kuliah</h4>
      <p class="text-secondary">Tim Admin Nugasin Class sedang menyiapkan daftar mata kuliah kamu. Silakan periksa kembali beberapa saat lagi.</p>
    </div>
  @else
    <!-- Nav Tabs for Sessions 1..8 -->
    <ul class="nav nav-pills nav-fill gap-2 mb-4 bg-body-tertiary p-2 rounded-4 border" id="sessionTabs" role="tablist">
      @for($session = 1; $session <= 8; $session++)
        <li class="nav-item" role="presentation">
          <button class="nav-link rounded-3 fw-bold {{ $session === 1 ? 'active' : '' }}"
                  id="tab-sesi-{{ $session }}"
                  data-bs-toggle="tab"
                  data-bs-target="#sesi-{{ $session }}"
                  type="button"
                  role="tab">
            Sesi {{ $session }}
          </button>
        </li>
      @endfor
    </ul>

    <!-- Session Tab Contents -->
    <div class="tab-content" id="sessionTabsContent">
      @for($session = 1; $session <= 8; $session++)
        <div class="tab-pane fade {{ $session === 1 ? 'show active' : '' }}" id="sesi-{{ $session }}" role="tabpanel">
          <div class="row g-4">
            @foreach($user->courses as $course)
              @php
                $progress = $course->progresses->firstWhere('session_number', $session);
              @endphp
              <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 card-hover">
                  <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                      <div>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill mb-1">Sesi {{ $session }}</span>
                        <h5 class="fw-bold mb-0">{{ $course->course_name }}</h5>
                        @if($course->course_code)
                          <div class="small text-secondary">{{ $course->course_code }}</div>
                        @endif
                      </div>
                    </div>

                    <hr class="my-3">

                    <!-- Diskusi Section -->
                    <div class="mb-3 p-3 rounded-3 bg-body-tertiary border">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold small">💬 Diskusi Sesi {{ $session }}</span>
                        @php
                          $dStatus = $progress->discussion_status ?? 'Belum Dikerjakan';
                          $dBadge = match($dStatus) {
                            'Selesai' => 'bg-success text-white',
                            'Menunggu Nilai' => 'bg-info text-dark',
                            'On Progress' => 'bg-warning text-dark',
                            default => 'bg-secondary text-white',
                          };
                        @endphp
                        <span class="badge {{ $dBadge }} rounded-pill small">{{ $dStatus }}</span>
                      </div>
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="text-secondary small">Nilai Diskusi:</span>
                        <span class="fw-bold fs-6 {{ ($progress && $progress->discussion_score !== null) ? 'text-primary' : 'text-secondary' }}">
                          {{ ($progress && $progress->discussion_score !== null) ? $progress->discussion_score : '-' }}
                        </span>
                      </div>
                    </div>

                    <!-- Tugas Section -->
                    <div class="mb-3 p-3 rounded-3 bg-body-tertiary border">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold small">📝 Tugas Sesi {{ $session }}</span>
                        @php
                          $tStatus = $progress->assignment_status ?? 'Belum Dikerjakan';
                          $tBadge = match($tStatus) {
                            'Selesai' => 'bg-success text-white',
                            'Menunggu Nilai' => 'bg-info text-dark',
                            'On Progress' => 'bg-warning text-dark',
                            default => 'bg-secondary text-white',
                          };
                        @endphp
                        <span class="badge {{ $tBadge }} rounded-pill small">{{ $tStatus }}</span>
                      </div>
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="text-secondary small">Nilai Tugas:</span>
                        <span class="fw-bold fs-6 {{ ($progress && $progress->assignment_score !== null) ? 'text-primary' : 'text-secondary' }}">
                          {{ ($progress && $progress->assignment_score !== null) ? $progress->assignment_score : '-' }}
                        </span>
                      </div>
                    </div>

                    @if($progress && $progress->notes)
                      <div class="p-3 bg-warning-subtle text-warning-emphasis rounded-3 small">
                        <strong>📌 Catatan Admin:</strong> {{ $progress->notes }}
                      </div>
                    @endif

                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endfor
    </div>
  @endif
</main>
@endsection
