@extends('store_backend.layouts.app')

@section('title', 'Menu Cabang ' . $store->name)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1 text-dark">Menu Makanan & Topping Cabang</h2>
        <p class="text-muted mb-0">Kelola harga, stok harian, foto produk, topping, dan upsize khusus cabang {{ $store->name }}.</p>
    </div>

    <a href="{{ route('store.backend.menus.create', $store->slug) }}" class="btn btn-primary">
        <i class="fa-solid fa-plus me-1"></i> Tambah Menu Baru
    </a>
</div>

<div class="card-custom">
    <div class="card-custom-header">
        <h5 class="card-custom-title"><i class="fa-solid fa-bowl-food text-primary me-2"></i> Daftar Menu Makanan & Varian</h5>
        <span class="badge bg-secondary">{{ $menus->count() }} Menu</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;">Foto</th>
                    <th>Nama Menu & Deskripsi</th>
                    <th>Kategori</th>
                    <th>Harga Standard</th>
                    <th>Reward Poin</th>
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
                                 alt="{{ $menu->name }}" class="rounded-3 border object-fit-cover shadow-sm" style="width: 54px; height: 54px;">
                        </td>
                        <td>
                            <div class="fw-bold fs-6">{{ $menu->name }}</div>
                            <small class="text-muted text-truncate d-inline-block" style="max-width: 260px;">
                                {{ $menu->description ?: '-' }}
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-info text-dark">{{ $menu->category->name ?? 'Tanpa Kategori' }}</span>
                        </td>
                        <td class="font-mono fw-bold text-success">
                            Rp {{ number_format($menu->price, 0, ',', '.') }}
                        </td>
                        <td>
                            @if(($menu->reward_points ?? 0) > 0)
                                <span class="badge bg-warning text-dark font-mono">
                                    <i class="fa-solid fa-coins me-1"></i> +{{ $menu->reward_points }} Pts
                                </span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $menu->current_stock < 10 ? 'bg-danger' : 'bg-success' }}">
                                {{ $menu->current_stock }} / {{ $menu->initial_stock }}
                            </span>
                        </td>
                        <td>
                            <small class="text-muted">
                                {{ $menu->options->count() }} varian ({{ $menu->options->where('type', 'topping')->count() }} topping, {{ $menu->options->where('type', 'upsize')->count() }} size)
                            </small>
                        </td>
                        <td>
                            <span class="badge {{ $menu->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $menu->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('store.backend.menus.edit', ['store_slug' => $store->slug, 'menu' => $menu->id]) }}" 
                                   class="btn btn-sm btn-outline-primary" title="Edit Menu">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('store.backend.menus.destroy', ['store_slug' => $store->slug, 'menu' => $menu->id]) }}" 
                                      method="POST" onsubmit="return confirm('Hapus menu {{ $menu->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Menu">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
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
