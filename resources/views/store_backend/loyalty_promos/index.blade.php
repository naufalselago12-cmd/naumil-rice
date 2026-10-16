@extends('store_backend.layouts.app')

@section('title', 'Promo Pelanggan Setia - ' . $store->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-tags text-primary me-2"></i> Promo Khusus Pelanggan Setia Cabang {{ $store->name }}</h4>
        <p class="text-muted small mb-0">Atur program promosi eksklusif: diskon persentase all menu, diskon per menu, potongan minimum belanja, dan pilihan reward (potongan vs poin lebih banyak).</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('store.backend.loyaltyCustomers.index', $store->slug) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-users me-1"></i> Data Pelanggan Setia
        </a>
        <a href="{{ route('store.backend.loyaltyPromos.create', $store->slug) }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> Buat Promo Baru
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card-custom">
    <div class="card-custom-header">
        <h6 class="card-custom-title"><i class="fa-solid fa-list me-2 text-primary"></i> Daftar Program Promo Aktif & Riwayat ({{ $promos->count() }})</h6>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nama Promo</th>
                    <th>Tipe Skema Diskon</th>
                    <th>Nilai Potongan</th>
                    <th>Pilihan Benefit Pelanggan</th>
                    <th>Periode Berlaku</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($promos as $promo)
                    <tr>
                        <td>
                            <div class="fw-bold fs-6">{{ $promo->title }}</div>
                            <small class="text-muted text-truncate d-block" style="max-width: 250px;">{{ $promo->description ?: '-' }}</small>
                        </td>
                        <td>
                            @if($promo->promo_type === 'all_menu_percent')
                                <span class="badge bg-primary"><i class="fa-solid fa-border-all me-1"></i> Persentase Semua Menu</span>
                            @elseif($promo->promo_type === 'per_menu')
                                <span class="badge bg-info text-dark"><i class="fa-solid fa-bowl-food me-1"></i> Khusus Per Menu ({{ $promo->menus->count() }} menu)</span>
                            @else
                                <span class="badge bg-success"><i class="fa-solid fa-money-bill-wave me-1"></i> Potongan Min. Belanja</span>
                            @endif
                        </td>
                        <td>
                            @if($promo->promo_type === 'all_menu_percent')
                                <strong class="text-success fs-6 font-mono">{{ $promo->discount_percent }}% OFF</strong>
                            @elseif($promo->promo_type === 'per_menu')
                                <small class="text-dark">
                                    @foreach($promo->menus->take(3) as $pm)
                                        <div>• {{ $pm->name }}: {{ $pm->pivot->discount_percent > 0 ? $pm->pivot->discount_percent . '%' : 'Rp ' . number_format($pm->pivot->discount_amount, 0, ',', '.') }}</div>
                                    @endforeach
                                    @if($promo->menus->count() > 3)
                                        <span class="text-muted">+ {{ $promo->menus->count() - 3 }} menu lainnya</span>
                                    @endif
                                </small>
                            @else
                                <strong class="text-success font-mono">Potongan Rp {{ number_format($promo->discount_amount, 0, ',', '.') }}</strong>
                                <small class="text-muted d-block">(Min. Belanja Rp {{ number_format($promo->min_purchase_amount, 0, ',', '.') }})</small>
                            @endif
                        </td>
                        <td>
                            @if($promo->allow_benefit_choice)
                                <div class="badge bg-warning-subtle text-dark border border-warning">
                                    <i class="fa-solid fa-code-compare text-warning me-1"></i> Bisa Pilih:
                                </div>
                                <small class="d-block mt-1 text-muted">
                                    Diskon <strong>ATAU</strong> Poin Lebih Banyak ({{ $promo->extra_points_multiplier }}x Poin{{ $promo->extra_points_bonus > 0 ? ' +' . $promo->extra_points_bonus . ' Pts' : '' }})
                                </small>
                            @else
                                <span class="badge bg-light text-muted border">Hanya Potongan Diskon</span>
                            @endif
                        </td>
                        <td>
                            @if($promo->start_date || $promo->end_date)
                                <small class="text-muted d-block">
                                    {{ $promo->start_date ? $promo->start_date->format('d/m/Y') : 'Sekarang' }} s/d {{ $promo->end_date ? $promo->end_date->format('d/m/Y') : 'Seterusnya' }}
                                </small>
                            @else
                                <small class="text-success fw-semibold"><i class="fa-solid fa-infinity me-1"></i> Berlaku Selamanya</small>
                            @endif
                        </td>
                        <td>
                            @if($promo->is_active)
                                <span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> Aktif</span>
                            @else
                                <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('store.backend.loyaltyPromos.edit', ['store_slug' => $store->slug, 'promo' => $promo->id]) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('store.backend.loyaltyPromos.destroy', ['store_slug' => $store->slug, 'promo' => $promo->id]) }}" method="POST" onsubmit="return confirm('Hapus program promo ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-tags fa-3x mb-3 text-secondary opacity-50"></i>
                            <div class="fw-bold">Belum Ada Program Promo Pelanggan Setia</div>
                            <small class="d-block mb-3">Buat promo eksklusif untuk menarik pelanggan bergabung menjadi anggota pelanggan setia.</small>
                            <a href="{{ route('store.backend.loyaltyPromos.create', $store->slug) }}" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-plus me-1"></i> Buat Promo Sekarang
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
