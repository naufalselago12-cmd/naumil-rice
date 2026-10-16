@extends('store_backend.layouts.app')

@section('title', 'Detail Pelanggan Setia - ' . $customer->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('store.backend.loyaltyCustomers.index', $store->slug) }}" class="btn btn-sm btn-outline-secondary mb-2">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Daftar Pelanggan
    </a>
    <div class="d-flex justify-content-between align-items-center">
        <h4 class="fw-bold mb-0">
            <i class="fa-solid fa-crown text-warning me-2"></i> Profil Pelanggan Setia: {{ $customer->name }}
        </h4>
        <div>
            @if($customer->is_active && $customer->email_verified_at)
                <span class="badge bg-success fs-6"><i class="fa-solid fa-circle-check me-1"></i> Akun Terverifikasi</span>
            @else
                <span class="badge bg-warning text-dark fs-6"><i class="fa-solid fa-clock me-1"></i> Menunggu Verifikasi Email</span>
            @endif
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4 mb-4">
    <!-- Customer Profile Info -->
    <div class="col-lg-5">
        <div class="card-custom h-100">
            <div class="card-custom-header">
                <h6 class="card-custom-title"><i class="fa-solid fa-id-card me-2 text-primary"></i> Data Identitas Pelanggan</h6>
            </div>
            <div class="p-4">
                <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                    <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center fw-bold fs-2" style="width: 64px; height: 64px;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">{{ $customer->name }}</h5>
                        <div class="d-flex gap-2">
                            <span class="badge {{ $customer->gender === 'Laki-laki' ? 'bg-primary' : 'bg-danger' }}">
                                {{ $customer->gender ?: 'Belum diisi' }}
                            </span>
                            <span class="badge bg-secondary">
                                <i class="fa-solid fa-briefcase me-1"></i> {{ $customer->occupation ?: 'Pekerjaan -' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="text-muted small d-block">Nomor HP / WhatsApp:</label>
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->whatsapp) }}" target="_blank" class="fw-bold text-success text-decoration-none fs-6">
                        <i class="fa-brands fa-whatsapp me-1"></i> {{ $customer->whatsapp }}
                    </a>
                </div>

                <div class="mb-3">
                    <label class="text-muted small d-block">Email Terdaftar:</label>
                    <span class="fw-bold font-mono">{{ $customer->email }}</span>
                </div>

                <div class="mb-3">
                    <label class="text-muted small d-block">Alamat Pengiriman:</label>
                    <p class="mb-0 text-dark small bg-light p-2 rounded border">{{ $customer->address ?: 'Alamat belum diatur' }}</p>
                </div>

                <div class="row g-2 pt-2 border-top">
                    <div class="col-6">
                        <small class="text-muted d-block">Bergabung Sejak:</small>
                        <span class="fw-semibold small">{{ $customer->created_at ? $customer->created_at->format('d M Y, H:i') : '-' }}</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Terakhir Pesan:</small>
                        <span class="fw-semibold small">{{ $customer->last_order_at ? $customer->last_order_at->format('d M Y, H:i') : 'Belum pernah' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Credit Points & Adjust Action -->
    <div class="col-lg-7">
        <div class="card-custom h-100">
            <div class="card-custom-header">
                <h6 class="card-custom-title"><i class="fa-solid fa-coins me-2 text-warning"></i> Credit Point & Reward</h6>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#adjustPointsModal">
                    <i class="fa-solid fa-sliders me-1"></i> Sesuaikan Poin Manual
                </button>
            </div>
            <div class="p-4">
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="p-3 rounded bg-warning-subtle border border-warning text-center">
                            <small class="text-muted d-block fw-bold text-uppercase">Poin di Cabang Ini ({{ $store->code }})</small>
                            <h2 class="fw-bold font-mono text-dark mb-0 mt-1">
                                <i class="fa-solid fa-coins text-warning me-1"></i> {{ number_format($storePoints, 0, ',', '.') }}
                            </h2>
                            <small class="text-muted">Poin dari transaksi di {{ $store->name }}</small>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded bg-primary-subtle border border-primary text-center">
                            <small class="text-muted d-block fw-bold text-uppercase">Total Saldo Poin Global</small>
                            <h2 class="fw-bold font-mono text-primary mb-0 mt-1">
                                {{ number_format($customer->loyalty_points, 0, ',', '.') }}
                            </h2>
                            <small class="text-muted">Dapat ditukarkan saat pemesanan</small>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left me-2 text-secondary"></i> Riwayat Mutasi Poin Terakhir</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu</th>
                                <th>Jenis</th>
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $t)
                                <tr>
                                    <td class="text-muted">{{ $t->created_at ? $t->created_at->format('d/m/Y H:i') : '-' }}</td>
                                    <td>
                                        <span class="badge {{ $t->points > 0 ? 'bg-success' : 'bg-danger' }}">
                                            {{ strtoupper($t->type) }}
                                        </span>
                                    </td>
                                    <td class="font-mono fw-bold {{ $t->points > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $t->points > 0 ? '+' : '' }}{{ $t->points }} Pts
                                    </td>
                                    <td>{{ $t->description }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">Belum ada riwayat mutasi poin.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order History at this store -->
<div class="card-custom">
    <div class="card-custom-header">
        <h6 class="card-custom-title"><i class="fa-solid fa-receipt me-2 text-primary"></i> Riwayat Pesanan di Cabang {{ $store->name }} ({{ $orders->count() }})</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nomor Pesanan</th>
                    <th>Waktu Pesan</th>
                    <th>Menu Dipesan</th>
                    <th>Total Belanja</th>
                    <th>Poin Diperoleh</th>
                    <th>Pilihan Promo / Benefit</th>
                    <th>Status Pesanan</th>
                    <th>Status Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                    <tr>
                        <td class="fw-bold font-mono">{{ $o->order_number }}</td>
                        <td class="text-muted small">{{ $o->created_at ? $o->created_at->format('d M Y, H:i') : '-' }}</td>
                        <td>
                            <small>
                                @foreach($o->items as $it)
                                    <div>• {{ $it->menu_name }} ({{ $it->quantity }}x)</div>
                                @endforeach
                            </small>
                        </td>
                        <td class="font-mono fw-bold text-danger">Rp {{ number_format($o->total_amount, 0, ',', '.') }}</td>
                        <td>
                            @if($o->points_earned > 0)
                                <span class="badge bg-warning text-dark font-mono"><i class="fa-solid fa-coins me-1"></i> +{{ $o->points_earned }} Pts</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>
                            @if($o->loyalty_benefit_choice === 'points')
                                <span class="badge bg-info text-dark"><i class="fa-solid fa-bolt me-1"></i> Poin Lebih Banyak</span>
                            @elseif($o->loyalty_benefit_choice === 'discount')
                                <span class="badge bg-success"><i class="fa-solid fa-percent me-1"></i> Potongan Diskon</span>
                            @else
                                <span class="text-muted small">Standard</span>
                            @endif
                        </td>
                        <td><span class="badge bg-primary">{{ $o->status }}</span></td>
                        <td>
                            <span class="badge {{ $o->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ strtoupper($o->payment_status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Pelanggan belum pernah melakukan transaksi di cabang ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Penyesuaian Poin Manual -->
<div class="modal fade" id="adjustPointsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('store.backend.loyaltyCustomers.adjustPoints', ['store_slug' => $store->slug, 'customer' => $customer->id]) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-sliders text-primary me-2"></i> Sesuaikan Credit Point Pelanggan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Tindakan</label>
                    <select name="action" class="form-select" required>
                        <option value="add">➕ Tambah Poin (Reward / Bonus Manual)</option>
                        <option value="deduct">➖ Potong Poin (Koreksi / Penukaran Manual)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Jumlah Poin <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="points" class="form-control" placeholder="Contoh: 50" min="1" required>
                        <span class="input-group-text font-mono">Pts</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Alasan / Catatan Penyesuaian <span class="text-danger">*</span></label>
                    <textarea name="note" class="form-control" rows="2" placeholder="Contoh: Bonus ulang tahun pelanggan / Kompensasi pesanan" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Penyesuaian Poin</button>
            </div>
        </form>
    </div>
</div>
@endsection
