@extends('layouts.admin')
@section('title', 'Pengajuan Pencairan - Cuanin')
@section('content')
<h1 class="h3 fw-bold">Pengajuan Pencairan Cuanin</h1><p>Periksa pengajuan dan catat hasil setelah transfer dilakukan.</p>
<div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Pengajuan</th><th>Pemilik</th><th>Nominal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($requests as $item)<tr><td>#CUAN-{{ $item->id }}</td><td>{{ $item->wallet->user->name }}</td><td>{{ \App\Support\Cuanin::format($item->amount) }}</td><td>{{ ['pending'=>'Menunggu proses', 'paid'=>'Selesai', 'rejected'=>'Ditolak'][$item->status] }}</td><td><a href="{{ route('cuanin.withdrawal', $item->id) }}" class="btn btn-outline-primary btn-sm rounded-pill">Detail</a></td></tr>@empty<tr><td colspan="5" class="text-secondary">Belum ada pengajuan.</td></tr>@endforelse
</tbody></table></div>{{ $requests->links() }}</div></div>
@endsection
