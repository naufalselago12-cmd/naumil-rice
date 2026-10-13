@extends('superadmin.layouts.app')

@section('title', 'Kelola User Per Toko')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1 text-white">Kelola User Admin Per Toko</h2>
        <p class="text-muted mb-0">Atur akun administrator dan tugaskan cabang toko yang dikelola masing-masing user.</p>
    </div>

    <a href="{{ route('superadmin.users.create') }}" class="btn btn-super">
        <i class="fa-solid fa-user-plus me-1"></i> Tambah User Baru
    </a>
</div>

<!-- Filters -->
<div class="card-dark mb-4">
    <div class="p-3">
        <form method="GET" action="{{ route('superadmin.users.index') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <select name="store_id" class="form-select form-select-dark" onchange="this.form.submit()">
                    <option value="">Semua Penugasan Cabang</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ $storeFilter == $st->id ? 'selected' : '' }}>
                            {{ $st->name }} ({{ $st->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <select name="role" class="form-select form-select-dark" onchange="this.form.submit()">
                    <option value="">Semua Role</option>
                    <option value="superadmin" {{ $roleFilter === 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                    <option value="store_admin" {{ $roleFilter === 'store_admin' ? 'selected' : '' }}>Store Admin</option>
                </select>
            </div>

            @if($storeFilter || $roleFilter)
                <div class="col-auto">
                    <a href="{{ route('superadmin.users.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-xmark me-1"></i> Reset Filter
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>

<!-- User Table -->
<div class="card-dark">
    <div class="card-dark-header">
        <h5 class="card-dark-title"><i class="fa-solid fa-users text-warning me-2"></i> Daftar Pengguna & Penugasan Toko</h5>
        <span class="badge bg-secondary">{{ $users->count() }} Pengguna</span>
    </div>

    <div class="table-responsive">
        <table class="table table-dark-custom align-middle">
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Toko Yang Dikelola (Assignment)</th>
                    <th>Akses Backend Toko</th>
                    <th style="width: 140px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $usr)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width: 38px; height: 38px; border-radius: 10px; background: {{ $usr->isSuperAdmin() ? 'linear-gradient(135deg, #f43f5e, #be123c)' : '#334155' }}; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white;">
                                    {{ strtoupper(substr($usr->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-white">{{ $usr->name }}</div>
                                    @if($usr->username === 'naufal_su')
                                        <span class="badge bg-danger" style="font-size: 0.65rem;">Master Super Admin</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <code class="text-info font-mono fw-bold">@<span>{{ $usr->username }}</span></code>
                        </td>
                        <td>
                            <span class="text-light">{{ $usr->email }}</span>
                        </td>
                        <td>
                            @if($usr->isSuperAdmin())
                                <span class="badge bg-danger">
                                    <i class="fa-solid fa-shield-halved me-1"></i> Super Admin
                                </span>
                            @else
                                <span class="badge bg-primary">
                                    <i class="fa-solid fa-store me-1"></i> Store Admin
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($usr->isSuperAdmin())
                                <span class="text-muted small">
                                    <i class="fa-solid fa-infinity me-1 text-danger"></i> Seluruh Cabang (Akses Penuh)
                                </span>
                            @elseif($usr->store)
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success font-mono">{{ $usr->store->code }}</span>
                                    <span class="fw-semibold text-white">{{ $usr->store->name }}</span>
                                </div>
                            @else
                                <span class="badge bg-warning text-dark">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Belum Ada Toko
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($usr->store)
                                <a href="{{ url('/' . ($usr->store->slug ?: $usr->store->code) . '/backend') }}" 
                                   target="_blank" class="btn btn-sm btn-outline-info" title="Buka backend toko yang dikelola user ini">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> /{{ $usr->store->slug }}/backend
                                </a>
                            @elseif($usr->isSuperAdmin())
                                <span class="badge bg-dark text-muted">Akses Semua Backend</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('superadmin.users.edit', $usr->id) }}" class="btn btn-sm btn-outline-light" title="Edit User">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                @if($usr->username !== 'naufal_su' && $usr->id !== auth()->id())
                                    <form action="{{ route('superadmin.users.destroy', $usr->id) }}" method="POST" 
                                          onsubmit="return confirm('Hapus user {{ $usr->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus User">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">Tidak ada user yang sesuai kriteria.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
