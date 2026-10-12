@extends('layouts.admin')

@section('title', 'Menu Makanan & Topping')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1">Manajemen Menu Makanan</h2>
        <p class="text-muted mb-0">Kelola harga, stok awal, gambar produk, topping, upsize, dan kondisi per menu.</p>
    </div>

    <div class="d-flex align-items-center gap-2">
        <!-- Store Selector Filter -->
        <form method="GET" action="{{ route('admin.menus.index') }}" class="d-flex align-items-center gap-2">
            <select name="store_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 200px;">
                @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ $selectedStoreId == $st->id ? 'selected' : '' }}>
                        {{ $st->name }}
                    </option>
                @endforeach
            </select>
        </form>

        <a href="{{ route('admin.menus.create', ['store_id' => $selectedStoreId]) }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Tambah Menu Baru
        </a>
    </div>
</div>

<div class="card-custom">
    <div class="card-custom-header">
        <h5 class="card-custom-title"><i class="fa-solid fa-bowl-food me-2 text-primary"></i> Daftar Menu Makanan & Varian</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 80px;">Foto</th>
                    <th>Nama Menu & Deskripsi</th>
                    <th>Kategori</th>
                    <th>Harga Standard</th>
                    <th>Stok (Sisa / Awal)</th>
                    <th>Topping & Upsize</th>
                    <th>Status</th>
                    <th style="width: 120px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($menus as $menu)
                    <tr>
                        <td>
                            <img src="{{ $menu->image ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=100&q=80' }}" 
                                 alt="{{ $menu->name }}" 
                                 class="rounded-3 border object-fit-cover shadow-sm" 
                                 style="width: 60px; height: 60px;">
                        </td>
                        <td>
                            <div class="fw-bold fs-6">{{ $menu->name }}</div>
                            <small class="text-muted text-truncate d-inline-block" style="max-width: 280px;">
                                {{ $menu->description ?: '-' }}
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $menu->category?->name ?: 'Tanpa Kategori' }}</span>
                        </td>
                        <td class="fw-bold text-success fs-6">
                            Rp {{ number_format($menu->price, 0, ',', '.') }}
                        </td>
                        <td>
                            <span class="badge {{ $menu->current_stock > 10 ? 'bg-info text-dark' : 'bg-danger' }}">
                                {{ $menu->current_stock }} / {{ $menu->initial_stock }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1" style="max-width: 220px;">
                                @php
                                    $toppingsCount = $menu->options->where('type', 'topping')->count();
                                    $upsizeCount = $menu->options->where('type', 'upsize')->count();
                                    $conditionCount = $menu->options->where('type', 'condition')->count();
                                @endphp
                                @if($toppingsCount > 0)
                                    <span class="badge bg-light text-dark border">
                                        <i class="fa-solid fa-egg text-warning me-1"></i> {{ $toppingsCount }} Topping
                                    </span>
                                @endif
                                @if($upsizeCount > 0)
                                    <span class="badge bg-light text-dark border">
                                        <i class="fa-solid fa-arrows-up-to-line text-primary me-1"></i> {{ $upsizeCount }} Upsize
                                    </span>
                                @endif
                                @if($conditionCount > 0)
                                    <span class="badge bg-light text-dark border">
                                        <i class="fa-solid fa-pepper-hot text-danger me-1"></i> {{ $conditionCount }} Kondisi
                                    </span>
                                @endif
                                @if($menu->options->isEmpty())
                                    <span class="text-muted small">Tanpa Opsi</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $menu->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $menu->is_active ? 'Aktif' : 'Non-Aktif' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.menus.edit', $menu->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.menus.destroy', $menu->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus menu ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">Belum ada menu makanan untuk cabang ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
