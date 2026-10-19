@extends('store_backend.layouts.app')

@section('title', 'Buat Campaign Open PO - ' . $store->name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card-custom">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-bullhorn me-2 text-primary"></i> Buat Campaign Open PO & QR Toko ({{ $store->name }})</h5>
                <a href="{{ route('store.backend.campaigns.index', $store->slug) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <form action="{{ route('store.backend.campaigns.store', $store->slug) }}" method="POST" enctype="multipart/form-data" class="p-4">
                @csrf

                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Judul Campaign Open PO <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" 
                               placeholder="Contoh: Open PO Batch 16 - Weekend Feast" value="{{ old('title') }}" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Deskripsi Campaign & Informasi Pre-Order</label>
                        <textarea name="description" rows="3" class="form-control" 
                                  placeholder="Deskripsi promo, ketentuan batas transfer/bayar, jangkauan pengiriman...">{{ old('description') }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Upload File Banner</label>
                        <input type="file" name="banner_file" class="form-control" accept="image/*">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Atau URL Banner Promo</label>
                        <input type="url" name="banner_url" class="form-control" 
                               value="https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200&q=80">
                    </div>

                    <div class="col-12"><hr class="my-3"></div>

                    <!-- Date & Stock Quota Configurations -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-danger">Batas Waktu Close Order <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="close_order_at" class="form-control" 
                               value="{{ \Carbon\Carbon::now()->addDays(3)->format('Y-m-d\TH:i') }}" required>
                        <small class="text-muted">Setelah waktu ini, form PO ditutup otomatis.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold text-success">Waktu Makanan Siap / Dikirim <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="ready_at" class="form-control" 
                               value="{{ \Carbon\Carbon::now()->addDays(4)->format('Y-m-d\TH:i') }}" required>
                        <small class="text-muted">Jadwal dapur menyelesaikan pesanan batch.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold text-primary">
                            <i class="fa-solid fa-boxes-stacked me-1"></i> Maks. Stok Campaign (Porsi)
                        </label>
                        <input type="number" name="max_quota" class="form-control" 
                               placeholder="Contoh: 100 (Opsional)" value="{{ old('max_quota') }}" min="1">
                        <small class="text-muted">Total kuota seluruh porsi untuk batch PO ini.</small>
                    </div>

                    <div class="col-12"><hr class="my-3"></div>

                    <!-- Discount Section -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tipe Promo / Diskon</label>
                        <select name="discount_type" class="form-select">
                            <option value="none">Tidak Ada Diskon</option>
                            <option value="percentage">Persentase (%)</option>
                            <option value="fixed">Nominal Tetap (Rp)</option>
                            <option value="min_purchase">Minimal Belanja</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Nilai Diskon (% atau Rp)</label>
                        <input type="number" name="discount_value" class="form-control" value="0" min="0">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Minimal Pembelian (Rp)</label>
                        <input type="number" name="min_purchase_amount" class="form-control" value="0" min="0">
                    </div>

                    <div class="col-12"><hr class="my-3"></div>

                    <!-- Select Menu for this campaign -->
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <label class="form-label fw-bold mb-0">
                                    <i class="fa-solid fa-utensils me-1 text-primary"></i> Menu Makanan Dalam Batch Open PO <span class="text-danger">*</span>
                                </label>
                                <p class="text-muted small mb-0">Menu yang dicentang di sini akan otomatis disembunyikan dari menu reguler dan hanya tampil di Open PO.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleSelectAllMenus()">
                                Centang / Batal Semua
                            </button>
                        </div>

                        <div class="table-responsive border rounded-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 45px;">Pilih</th>
                                        <th>Menu Makanan</th>
                                        <th style="width: 140px;">Harga Standard</th>
                                        <th style="width: 150px;">Diskon Menu (Rp)</th>
                                        <th style="width: 160px;">Harga Khusus PO</th>
                                        <th style="width: 160px;">Maks. Stok PO (Porsi)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($menus as $menu)
                                        <tr>
                                            <td>
                                                <input class="form-check-input menu-checkbox" type="checkbox" 
                                                       name="selected_menus[]" value="{{ $menu->id }}" id="menu_{{ $menu->id }}" checked>
                                            </td>
                                            <td>
                                                <label class="d-flex align-items-center gap-2 mb-0 cursor-pointer" for="menu_{{ $menu->id }}">
                                                    <img src="{{ $menu->image }}" alt="" class="rounded border" style="width: 42px; height: 42px; object-fit: cover;">
                                                    <div>
                                                        <div class="fw-bold">{{ $menu->name }}</div>
                                                        <small class="text-muted">{{ $menu->category?->name ?: 'Umum' }} &bull; Stok Harian: {{ $menu->current_stock }}</small>
                                                    </div>
                                                </label>
                                            </td>
                                            <td class="fw-semibold font-mono">Rp {{ number_format($menu->price, 0, ',', '.') }}</td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" name="menu_discounts[{{ $menu->id }}]" 
                                                           class="form-control" placeholder="0" value="0" min="0">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" name="menu_custom_prices[{{ $menu->id }}]" 
                                                           class="form-control" placeholder="{{ $menu->price }}" min="0">
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" name="menu_max_quotas[{{ $menu->id }}]" 
                                                       class="form-control form-control-sm" placeholder="Tak terbatas" min="1">
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                Belum ada menu di cabang ini. Silakan buat menu terlebih dahulu.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('store.backend.campaigns.index', $store->slug) }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-save me-1"></i> Terbitkan Open PO & QR
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleSelectAllMenus() {
        const checkboxes = document.querySelectorAll('.menu-checkbox');
        const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !anyChecked);
    }
</script>
@endpush
