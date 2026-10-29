@extends('layouts.admin')

@section('title', 'Kelola Paket Layanan - Admin Backoffice')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h2 class="fw-bold mb-1">Kelola Paket Layanan</h2>
    <p class="text-secondary mb-0">Atur daftar paket, harga, dan fitur pendampingan Nugasin Class.</p>
  </div>
  <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#addPackageModal">
    + Tambah Paket Baru
  </button>
</div>

<div class="row g-4 mb-4">
  @foreach($packages as $pkg)
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card h-100 border-0 shadow-sm rounded-4 bg-body">
        <div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="fw-bold mb-0">{{ $pkg->name }}</h5>
            @if($pkg->is_recommended)
              <span class="badge bg-primary rounded-pill">Rekomendasi</span>
            @endif
          </div>

          <div class="text-primary fw-extrabold fs-4 mb-2">
            Rp {{ number_format($pkg->price, 0, ',', '.') }}
            <span class="fs-6 text-secondary font-normal">/ {{ $pkg->price_label ?: 'layanan' }}</span>
          </div>

          <p class="text-secondary small mb-3">{{ $pkg->description }}</p>

          @if($pkg->features)
            <div class="small text-secondary fw-semibold mb-2">Fitur Paket:</div>
            <ul class="list-unstyled small mb-4">
              @foreach($pkg->features as $feat)
                <li class="d-flex gap-2 mb-1"><span class="text-success">✓</span><span>{{ $feat }}</span></li>
              @endforeach
            </ul>
          @endif

          <div class="d-flex gap-2 mt-auto pt-3 border-top">
            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill flex-fill" data-bs-toggle="modal" data-bs-target="#editPackageModal{{ $pkg->id }}">
              ✏️ Edit Paket
            </button>
            <form action="{{ route('admin.packages.destroy', $pkg->id) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Hapus paket ini?')">🗑️</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Edit Package -->
    <div class="modal fade" id="editPackageModal{{ $pkg->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
          <form action="{{ route('admin.packages.update', $pkg->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-header">
              <h5 class="modal-title fw-bold">Edit Paket: {{ $pkg->name }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
              <div class="mb-3">
                <label class="form-label fw-semibold">Nama Paket</label>
                <input type="text" name="name" class="form-control rounded-3" value="{{ $pkg->name }}" required>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-6">
                  <label class="form-label fw-semibold">Harga (Rp)</label>
                  <input type="number" name="price" class="form-control rounded-3" value="{{ $pkg->price }}" required>
                </div>
                <div class="col-6">
                  <label class="form-label fw-semibold">Label Durasi</label>
                  <input type="text" name="price_label" class="form-control rounded-3" value="{{ $pkg->price_label }}" placeholder="e.g. / semester">
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Deskripsi Singkat</label>
                <input type="text" name="description" class="form-control rounded-3" value="{{ $pkg->description }}">
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Fitur (1 fitur per baris)</label>
                <textarea name="features" class="form-control rounded-3" rows="4">{{ is_array($pkg->features) ? implode("\n", $pkg->features) : '' }}</textarea>
              </div>

              <div class="form-check mb-2">
                <input type="checkbox" name="is_recommended" class="form-check-input" id="editRec{{ $pkg->id }}" {{ $pkg->is_recommended ? 'checked' : '' }}>
                <label class="form-check-label" for="editRec{{ $pkg->id }}">Tandai sebagai Paket Rekomendasi</label>
              </div>

              <div class="form-check">
                <input type="checkbox" name="is_active" class="form-check-input" id="editAct{{ $pkg->id }}" {{ $pkg->is_active ? 'checked' : '' }}>
                <label class="form-check-label" for="editAct{{ $pkg->id }}">Aktifkan Paket ini</label>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endforeach
</div>

<!-- Modal Add Package -->
<div class="modal fade" id="addPackageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0">
      <form action="{{ route('admin.packages.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Tambah Paket Layanan Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nama Paket</label>
            <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: Paket Full Semester" required>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold">Harga (Rp)</label>
              <input type="number" name="price" class="form-control rounded-3" placeholder="500000" required>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Label Durasi</label>
              <input type="text" name="price_label" class="form-control rounded-3" placeholder="e.g. / semester">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Deskripsi Singkat</label>
            <input type="text" name="description" class="form-control rounded-3" placeholder="Penjelasan singkat mengenai paket">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Fitur (1 fitur per baris)</label>
            <textarea name="features" class="form-control rounded-3" rows="4" placeholder="Absen sesi&#10;Pengerjaan Diskusi&#10;Pengerjaan Tugas Teori"></textarea>
          </div>

          <div class="form-check mb-2">
            <input type="checkbox" name="is_recommended" class="form-check-input" id="addRec">
            <label class="form-check-label" for="addRec">Tandai sebagai Paket Rekomendasi</label>
          </div>

          <div class="form-check">
            <input type="checkbox" name="is_active" class="form-check-input" id="addAct" checked>
            <label class="form-check-label" for="addAct">Aktifkan Paket ini</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Paket</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
