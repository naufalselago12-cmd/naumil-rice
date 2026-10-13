@extends('superadmin.layouts.app')

@section('title', 'Kelola Cabang / Toko')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1 text-white">Kelola Cabang & URL Toko</h2>
        <p class="text-muted mb-0">Atur profil cabang, slug URL toko <code>base_url/(nama_toko)</code>, dan backend masing-masing.</p>
    </div>

    <a href="{{ route('superadmin.stores.create') }}" class="btn btn-super">
        <i class="fa-solid fa-plus me-1"></i> Tambah Cabang Baru
    </a>
</div>

<div class="card-dark">
    <div class="card-dark-header">
        <h5 class="card-dark-title"><i class="fa-solid fa-store text-danger me-2"></i> Daftar Seluruh Cabang (Tenants)</h5>
        <span class="badge bg-secondary">{{ $stores->count() }} Cabang Terdaftar</span>
    </div>

    <div class="table-responsive">
        <table class="table table-dark-custom align-middle">
            <thead>
                <tr>
                    <th style="width: 70px;">Logo</th>
                    <th>Nama & Kode Toko</th>
                    <th>Slug URL Frontend</th>
                    <th>URL Backend Toko</th>
                    <th>Admin Pengelola</th>
                    <th>Statistik</th>
                    <th>Status</th>
                    <th style="width: 160px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stores as $st)
                    <tr>
                        <td>
                            <img src="{{ $st->logo ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=100&q=80' }}" 
                                 alt="{{ $st->name }}" class="rounded-3 border border-secondary" style="width: 48px; height: 48px; object-fit: cover;">
                        </td>
                        <td>
                            <div class="fw-bold text-white fs-6">{{ $st->name }}</div>
                            <span class="badge bg-danger font-mono">{{ $st->code }}</span>
                            <small class="text-muted d-block mt-1 text-truncate" style="max-width: 200px;">
                                <i class="fa-solid fa-location-dot me-1"></i> {{ $st->address }}
                            </small>
                        </td>
                        <td>
                            <a href="{{ url('/' . ($st->slug ?: $st->code)) }}" target="_blank" class="text-info fw-semibold text-decoration-none">
                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> /{{ $st->slug ?: Str::slug($st->name) }}
                            </a>
                        </td>
                        <td>
                            <a href="{{ url('/' . ($st->slug ?: $st->code) . '/backend') }}" target="_blank" class="badge bg-primary text-decoration-none p-2">
                                <i class="fa-solid fa-gauge-simple-high me-1"></i> /{{ $st->slug ?: Str::slug($st->name) }}/backend
                            </a>
                        </td>
                        <td>
                            @if($st->users->count() > 0)
                                @foreach($st->users as $u)
                                    <span class="badge bg-secondary d-inline-block mb-1">
                                        {{ $u->name }} (@<span>{{ $u->username }}</span>)
                                    </span>
                                @endforeach
                            @else
                                <span class="badge bg-dark text-warning border border-warning border-opacity-40">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Belum ada admin
                                </span>
                            @endif
                        </td>
                        <td>
                            <small class="text-light d-block"><i class="fa-solid fa-bowl-food me-1 text-muted"></i> {{ $st->menus_count }} Menu</small>
                            <small class="text-light d-block"><i class="fa-solid fa-bullhorn me-1 text-muted"></i> {{ $st->campaigns_count }} PO</small>
                            <small class="text-light d-block"><i class="fa-solid fa-receipt me-1 text-muted"></i> {{ $st->orders_count }} Pesanan</small>
                        </td>
                        <td>
                            <span class="badge {{ $st->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ strtoupper($st->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('superadmin.stores.edit', $st->id) }}" class="btn btn-sm btn-outline-light" title="Edit Cabang">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('superadmin.stores.destroy', $st->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus cabang {{ $st->name }}? Seluruh menu dan order terkait akan ikut terhapus!');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Cabang">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">Belum ada cabang terdaftar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
