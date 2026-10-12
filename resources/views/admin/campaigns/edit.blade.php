@extends('layouts.admin')

@section('title', 'Edit Campaign ' . $campaign->title)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card-custom">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Edit Campaign Open PO</h5>
                <a href="{{ route('admin.campaigns.show', $campaign->id) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <form action="{{ route('admin.campaigns.update', $campaign->id) }}" method="POST" enctype="multipart/form-data" class="p-4">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Cabang / Toko</label>
                        <input type="text" class="form-control" value="{{ $campaign->store->name }} ({{ $campaign->store->code }})" readonly disabled>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Judul Campaign Open PO <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" 
                               value="{{ old('title', $campaign->title) }}" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Deskripsi Campaign</label>
                        <textarea name="description" rows="3" class="form-control">{{ old('description', $campaign->description) }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Banner Baru (Upload)</label>
                        <input type="file" name="banner_file" class="form-control" accept="image/*">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Atau URL Banner</label>
                        <input type="url" name="banner_url" class="form-control" value="{{ old('banner_url', $campaign->banner_image) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Batas Waktu Close Order <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="close_order_at" class="form-control" 
                               value="{{ old('close_order_at', $campaign->close_order_at->format('Y-m-d\TH:i')) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Estimasi Makanan Ready <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="ready_at" class="form-control" 
                               value="{{ old('ready_at', $campaign->ready_at->format('Y-m-d\TH:i')) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tipe Diskon Campaign</label>
                        <select name="discount_type" class="form-select">
                            <option value="none" {{ $campaign->discount_type === 'none' ? 'selected' : '' }}>Tanpa Diskon Campaign</option>
                            <option value="fixed" {{ $campaign->discount_type === 'fixed' ? 'selected' : '' }}>Potongan Tetap (Rp)</option>
                            <option value="percentage" {{ $campaign->discount_type === 'percentage' ? 'selected' : '' }}>Persentase (%)</option>
                            <option value="min_purchase" {{ $campaign->discount_type === 'min_purchase' ? 'selected' : '' }}>Syarat Minimum Pembelian</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Nilai Diskon</label>
                        <input type="number" name="discount_value" class="form-control" value="{{ old('discount_value', $campaign->discount_value) }}" min="0">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Minimum Pembelian</label>
                        <input type="number" name="min_purchase_amount" class="form-control" value="{{ old('min_purchase_amount', $campaign->min_purchase_amount) }}" min="0">
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ $campaign->is_active ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="isActive">Status Campaign Aktif</label>
                        </div>
                    </div>

                    <!-- Menu Selection -->
                    <div class="col-12"><hr class="my-3"></div>

                    <div class="col-12">
                        <h6 class="fw-bold text-success mb-2">Pilih Menu untuk Open PO</h6>
                        <div class="table-responsive border rounded-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">Pilih</th>
                                        <th>Menu</th>
                                        <th>Harga Standard</th>
                                        <th style="width: 170px;">Diskon Menu (Rp)</th>
                                        <th style="width: 170px;">Harga Khusus PO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $selectedMenuIds = $campaign->menus->pluck('id')->toArray();
                                    @endphp
                                    @foreach($menus as $menu)
                                        @php
                                            $isChecked = in_array($menu->id, $selectedMenuIds);
                                            $pivot = $campaign->menus->where('id', $menu->id)->first()?->pivot;
                                            $disc = $pivot?->discount_price ?? 0;
                                            $custom = $pivot?->custom_price ?? '';
                                        @endphp
                                        <tr>
                                            <td>
                                                <input class="form-check-input" type="checkbox" 
                                                       name="selected_menus[]" value="{{ $menu->id }}" {{ $isChecked ? 'checked' : '' }}>
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $menu->name }}</div>
                                            </td>
                                            <td>Rp {{ number_format($menu->price, 0, ',', '.') }}</td>
                                            <td>
                                                <input type="number" name="menu_discounts[{{ $menu->id }}]" 
                                                       class="form-control form-control-sm" value="{{ $disc }}" min="0">
                                            </td>
                                            <td>
                                                <input type="number" name="menu_custom_prices[{{ $menu->id }}]" 
                                                       class="form-control form-control-sm" value="{{ $custom }}" min="0">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.campaigns.show', $campaign->id) }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-save me-1"></i> Perbarui Campaign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
