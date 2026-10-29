<div class="card border-0 shadow-sm rounded-4 mb-4">
  <div class="card-body p-4">
    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start mb-3">
      <div><h2 class="h5 fw-bold mb-1">Progres Sesi per Client</h2><p class="small text-secondary mb-0">Sesi terakhir menunjukkan aktivitas diskusi/tugas yang sudah dimulai. Sesi selesai dihitung berurutan sejak sesi 1, ketika diskusi dan tugas selesai di seluruh mata kuliah.</p></div>
      <a class="btn btn-outline-primary btn-sm rounded-pill flex-shrink-0" href="{{ route('admin.academic.index') }}">Kelola Progres</a>
    </div>
    <form method="GET" action="{{ route('admin.dashboard') }}" class="d-flex gap-2 mb-3"><input name="client_search" class="form-control" value="{{ $search }}" placeholder="Cari nama atau nomor HP client" maxlength="100" aria-label="Cari progres client"><button class="btn btn-outline-primary">Cari</button>@if($search !== '')<a class="btn btn-outline-secondary" href="{{ route('admin.dashboard') }}">Reset</a>@endif</form>
    <div class="table-responsive"><table class="table align-middle table-hover"><thead><tr><th>Client</th><th>Handler</th><th>Aktivitas Terakhir</th><th>Selesai Seluruh Matkul</th><th>Rincian Mata Kuliah</th><th>Aksi</th></tr></thead><tbody>
    @forelse($progressClients as $client)
      @php($summary = $client->session_summary)
      <tr>
        <td><div class="fw-semibold">{{ $client->name }}</div><div class="small text-secondary">{{ $client->nomor_hp }}</div></td>
        <td>@if($client->handlerAdmin)<span class="badge {{ (int) $client->handler_admin_id === (int) auth()->id() ? 'bg-success' : 'bg-primary-subtle text-primary' }} rounded-pill">{{ (int) $client->handler_admin_id === (int) auth()->id() ? 'Saya' : $client->handlerAdmin->name }}</span>@else<span class="text-secondary">Belum diambil</span>@endif</td>
        <td>@if(!$client->courses->count())<span class="text-secondary">Belum ada mata kuliah</span>@elseif($summary['latest'] === 0)<span class="badge bg-secondary">Belum dimulai</span>@else<span class="badge bg-primary-subtle text-primary">Sesi {{ $summary['latest'] }} / 8</span>@endif</td>
        <td style="min-width:150px"><div class="small fw-semibold mb-1">{{ $summary['completed'] }} / 8 sesi</div><div class="progress" role="progressbar" aria-label="Sesi selesai {{ $client->name }}" aria-valuenow="{{ $summary['completed'] }}" aria-valuemin="0" aria-valuemax="8" style="height:8px"><div class="progress-bar bg-success" style="width:{{ $summary['completed'] * 12.5 }}%"></div></div></td>
        <td>@if(count($summary['courses']))<details><summary class="small">{{ count($summary['courses']) }} mata kuliah</summary><ul class="small ps-3 mt-2 mb-0">@foreach($summary['courses'] as $course)<li class="mb-1"><strong>{{ $course['name'] }}</strong>: {{ $course['latest'] ? 'aktivitas sesi '.$course['latest'] : 'belum dimulai' }}; selesai {{ $course['completed'] }}/8</li>@endforeach</ul></details>@else<span class="text-secondary">—</span>@endif</td>
        <td><a class="btn btn-sm btn-outline-primary rounded-pill text-nowrap" href="{{ route('admin.academic.show', $client->id) }}">Lihat Progres</a></td>
      </tr>
    @empty<tr><td colspan="6" class="text-center text-secondary py-4">Client tidak ditemukan.</td></tr>@endforelse
    </tbody></table></div>{{ $progressClients->links() }}
  </div>
</div>
