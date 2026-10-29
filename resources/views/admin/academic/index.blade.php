@extends('layouts.admin')

@section('title', 'Academic Progress Clients - Admin Backoffice')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h2 class="fw-bold mb-1">Academic Progress Management</h2>
    <p class="text-secondary mb-0">Kelola mata kuliah, progress Sesi 1–8, status diskusi/tugas, nilai, dan catatan client.</p>
  </div>
</div>

<div class="card border-0 shadow-sm rounded-4 bg-body mb-4">
  <div class="card-body p-4">
    <form action="{{ route('admin.academic.index') }}" method="GET" class="row g-3">
      <div class="col-12 col-md-9">
        <input type="text" name="search" class="form-control rounded-3" placeholder="Cari nama client, HP, universitas, nim, prodi..." value="{{ request('search') }}">
      </div>
      <div class="col-12 col-md-3">
        <button type="submit" class="btn btn-primary w-100 rounded-3">Cari Client</button>
      </div>
    </form>
  </div>
</div>

<div class="card border-0 shadow-sm rounded-4 bg-body">
  <div class="card-body p-4">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr class="text-secondary small">
            <th>Client</th>
            <th>Program Studi & Smt</th>
            <th>Mata Kuliah Aktif</th>
            <th>Handler</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($clients as $client)
            <tr>
              <td>
                <div class="fw-bold">{{ $client->name }}</div>
                <div class="small text-secondary">{{ $client->nomor_hp }}</div>
              </td>
              <td>
                @if($client->profile)
                  <div class="fw-semibold">{{ $client->profile->universitas }}</div>
                  <div class="small text-secondary">{{ $client->profile->program_studi }} (Smt {{ $client->profile->semester }})</div>
                @else
                  <span class="text-secondary fst-italic">Belum Onboarding</span>
                @endif
              </td>
              <td>
                <span class="badge bg-primary rounded-pill fs-6 px-3 py-1">{{ $client->courses->count() }} Mata Kuliah</span>
              </td>
              <td>
                @if($client->handlerAdmin)
                  @if((int) $client->handler_admin_id === (int) auth()->id())
                    <div class="d-flex flex-wrap align-items-center gap-2">
                      <span class="badge bg-success rounded-pill">Saya yang handle</span>
                      <form method="POST" action="{{ route('admin.academic.release', $client->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="return confirm('Lepas client {{ $client->name }} agar admin lain bisa ambil alih?')">Lepas</button>
                      </form>
                    </div>
                  @else
                    <span class="badge bg-primary-subtle text-primary rounded-pill">Di-handle {{ $client->handlerAdmin->name }}</span>
                  @endif
                @else
                  <form method="POST" action="{{ route('admin.academic.claim', $client->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold">Ambil Client</button>
                  </form>
                @endif
              </td>
              <td>
                <a href="{{ route('admin.academic.show', $client->id) }}" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
                  ✏️ Input & Update Progress (Sesi 1-8) →
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-secondary py-4">Client tidak ditemukan.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      {{ $clients->links() }}
    </div>
  </div>
</div>
@endsection
