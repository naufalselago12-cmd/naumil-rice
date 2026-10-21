@extends('layouts.admin')

@section('title', 'Buat Campaign Open PO Baru')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card-custom">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-bullhorn me-2 text-primary"></i> Buat Campaign Open PO & Generate QR Toko</h5>
                <a href="{{ route('admin.campaigns.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <form action="{{ route('admin.campaigns.store') }}" method="POST" enctype="multipart/form-data" class="p-4">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pilih Cabang / Toko <span class="text-danger">*</span></label>
                        <select name="store_id" class="form-select @error('store_id') is-invalid @enderror" 
                                onchange="location.href='{{ route('admin.campaigns.create') }}?store_id=' + this.value" required>
                            @foreach($stores as $st)
                                <option value="{{ $st->id }}" {{ $selectedStoreId == $st->id ? 'selected' : '' }}>
                                    {{ $st->name }} ({{ $st->code }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">QR code yang digenerate akan spesifik terhubung dengan cabang ini.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Judul Campaign Open PO <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" 
                               placeholder="Contoh: Open PO Batch 16 - Weekend Feast" value="{{ old('title') }}" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Deskripsi Campaign & Informasi Pre-Order</label>
                        <textarea name="description" rows="3" class="form-control" 
                                  placeholder="Deskripsi promo, ketentuan batas transfer/bayar, jangkauan kurir pengiriman...">{{ old('description') }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Banner Campaign (Upload File)</label>
                        <input type="file" name="banner_file" class="form-control" accept="image/*">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Atau URL Banner Promo</label>
                        <input type="url" name="banner_url" class="form-control" 
                               placeholder="https://images.unsplash.com/..." 
                               value="https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200&q=80">
                    </div>

                    <div class="col-12"><hr class="my-3"></div>

                    <!-- Date Configurations -->
                    <div class="col-12">
                        <h6 class="fw-bold text-danger mb-1">
                            <i class="fa-solid fa-clock me-2"></i> Jadwal Close Order & Tanggal Ready Makanan
                        </h6>
                        <p class="text-muted small">QR code campaign akan otomatis expired bila masa close order telah berakhir.</p>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold text-danger">Batas Waktu Close Order (Expired QR) <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="close_order_at" class="form-control @error('close_order_at') is-invalid @enderror" 
                               value="{{ old('close_order_at', now()->addDays(2)->format('Y-m-d\TH:i')) }}" required>
                        @error('close_order_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold text-success">Estimasi Makanan Ready / Dikirim <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="ready_at" class="form-control @error('ready_at') is-invalid @enderror" 
                               value="{{ old('ready_at', now()->addDays(3)->setTime(11, 0)->format('Y-m-d\TH:i')) }}" required>
                        @error('ready_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold text-primary">
                            <i class="fa-solid fa-boxes-stacked me-1"></i> Maks. Stok Campaign (Porsi)
                        </label>
                        <input type="number" name="max_quota" class="form-control" 
                               placeholder="Contoh: 100 (Opsional)" value="{{ old('max_quota') }}" min="1">
                        <small class="text-muted">Total kuota seluruh porsi pesanan batch ini.</small>
                    </div>

                    <div class="col-12"><hr class="my-3"></div>

                    <!-- Discount Settings -->
                    <div class="col-12">
                        <h6 class="fw-bold text-primary mb-1">
                            <i class="fa-solid fa-percent me-2"></i> Skema Diskon & Ketentuan Promo Open PO
                        </h6>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tipe Diskon Campaign</label>
                        <select name="discount_type" class="form-select" id="discountTypeSelect">
                            <option value="none">Tanpa Diskon Campaign</option>
                            <option value="min_qty" selected>Diskon Minimum Qty Pembelian (All Menu)</option>
                            <option value="percentage">Persentase (%)</option>
                            <option value="fixed">Potongan Tetap (Rp)</option>
                        </select>
                        <small class="text-muted">Pilih skema promo untuk batch Open PO ini.</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Nilai Diskon (% atau Rp)</label>
                        <input type="number" name="discount_value" class="form-control" placeholder="Contoh: 10" value="{{ old('discount_value', 10) }}" min="0">
                        <small class="text-muted">Persentase (%) untuk diskon min qty / all menu, atau nominal Rp.</small>
                    </div>

                    <div class="col-md-3" id="minOrderQtyGroup">
                        <label class="form-label fw-bold">Min. Qty Pembelian (Porsi)</label>
                        <input type="number" name="min_order_qty" class="form-control" placeholder="Contoh: 15" value="{{ old('min_order_qty', 15) }}" min="1">
                        <small class="text-muted">Diskon promo aktif jika pesanan mencapai jumlah porsi ini.</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Kuota Early Bird (Order)</label>
                        <input type="number" name="early_bird_quota" class="form-control" placeholder="Contoh: 5" value="{{ old('early_bird_quota', 5) }}" min="0">
                        <small class="text-muted">5 order pertama otomatis dapat diskon per menu langsung.</small>
                    </div>

                    <div class="col-12"><hr class="my-3"></div>

                    <!-- Menu Selection for PO -->
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="fw-bold text-success mb-1">
                                    <i class="fa-solid fa-utensils me-2"></i> Pilih Menu Makanan yang Diikutkan Open PO
                                </h6>
                                <p class="text-muted small mb-0">Centang menu yang ingin dibuka untuk pre-order dan tentukan diskon serta batas stok per menu.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleSelectAllMenus()">
                                Centang / Batal Semua
                            </button>
                        </div>

                        <div class="table-responsive border rounded-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">Pilih</th>
                                        <th>Menu Makanan</th>
                                        <th style="width: 130px;">Harga Standard</th>
                                        <th style="width: 150px;">Diskon Menu (Rp)</th>
                                        <th style="width: 150px;">Harga Khusus PO</th>
                                        <th style="width: 150px;">Maks. Stok PO (Porsi)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($menus as $menu)
                                        <tr>
                                            <td>
                                                <input class="form-check-input menu-checkbox" type="checkbox" 
                                                       name="selected_menus[]" value="{{ $menu->id }}" checked id="menu_{{ $menu->id }}">
                                            </td>
                                            <td>
                                                <label class="d-flex align-items-center gap-2 mb-0 cursor-pointer" for="menu_{{ $menu->id }}">
                                                    <img src="{{ $menu->image }}" alt="" class="rounded border" style="width: 44px; height: 44px; object-fit: cover;">
                                                    <div>
                                                        <div class="fw-bold">{{ $menu->name }}</div>
                                                        <small class="text-muted">{{ $menu->category?->name ?: 'Umum' }} &bull; Stok Harian: {{ $menu->current_stock }}</small>
                                                    </div>
                                                </label>
                                            </td>
                                            <td class="fw-semibold">Rp {{ number_format($menu->price, 0, ',', '.') }}</td>
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

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.campaigns.index') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-qrcode me-1"></i> Simpan & Generate QR Code
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
