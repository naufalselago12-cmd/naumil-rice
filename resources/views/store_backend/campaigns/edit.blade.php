@extends('store_backend.layouts.app')

@section('title', 'Edit Campaign - ' . $campaign->title)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card-custom">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Edit Campaign PO: {{ $campaign->title }}</h5>
                <a href="{{ route('store.backend.campaigns.index', $store->slug) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <form action="{{ route('store.backend.campaigns.update', ['store_slug' => $store->slug, 'campaign' => $campaign->id]) }}" 
                  method="POST" enctype="multipart/form-data" class="p-4">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-9">
                        <label class="form-label fw-bold">Judul Campaign Open PO <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $campaign->title) }}" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Status Campaign</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ old('is_active', $campaign->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="isActive">Aktif</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Deskripsi Campaign</label>
                        <textarea name="description" rows="3" class="form-control">{{ old('description', $campaign->description) }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Ganti Banner (Upload File)</label>
                        <input type="file" name="banner_file" class="form-control" accept="image/*">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Atau URL Banner</label>
                        <input type="url" name="banner_url" class="form-control" value="{{ old('banner_url', $campaign->banner_image) }}">
                    </div>

                    <div class="col-12"><hr class="my-3"></div>

                    <!-- Date Configurations -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-danger">Batas Waktu Close Order <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="close_order_at" class="form-control" 
                               value="{{ $campaign->close_order_at->format('Y-m-d\TH:i') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold text-success">Waktu Makanan Siap / Dikirim <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="ready_at" class="form-control" 
                               value="{{ $campaign->ready_at->format('Y-m-d\TH:i') }}" required>
                    </div>

                    <div class="col-12"><hr class="my-3"></div>

                    <!-- Discount Section -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tipe Promo / Diskon</label>
                        <select name="discount_type" class="form-select">
                            <option value="none" {{ $campaign->discount_type === 'none' ? 'selected' : '' }}>Tidak Ada Diskon</option>
                            <option value="percentage" {{ $campaign->discount_type === 'percentage' ? 'selected' : '' }}>Persentase (%)</option>
                            <option value="fixed" {{ $campaign->discount_type === 'fixed' ? 'selected' : '' }}>Nominal Tetap (Rp)</option>
                            <option value="min_purchase" {{ $campaign->discount_type === 'min_purchase' ? 'selected' : '' }}>Minimal Belanja</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Nilai Diskon (% atau Rp)</label>
                        <input type="number" name="discount_value" class="form-control" value="{{ $campaign->discount_value }}" min="0">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Minimal Pembelian (Rp)</label>
                        <input type="number" name="min_purchase_amount" class="form-control" value="{{ $campaign->min_purchase_amount }}" min="0">
                    </div>

                    <div class="col-12"><hr class="my-3"></div>

                    <!-- Select Menu for this campaign -->
                    <div class="col-12">
                        <label class="form-label fw-bold"><i class="fa-solid fa-utensils me-1 text-primary"></i> Menu Makanan Dalam Batch Ini <span class="text-danger">*</span></label>
                        @php
                            $campaignMenuIds = $campaign->menus->pluck('id')->toArray();
                        @endphp
                        <div class="row g-3">
                            @foreach($menus as $menu)
                                <div class="col-md-6">
                                    <div class="card p-3 border rounded-3 bg-light h-100">
                                        <div class="form-check">
                                            <input class="form-check-input menu-checkbox" type="checkbox" 
                                                   name="selected_menus[]" value="{{ $menu->id }}" id="menu{{ $menu->id }}"
                                                   {{ in_array($menu->id, $campaignMenuIds) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold" for="menu{{ $menu->id }}">
                                                {{ $menu->name }}
                                            </label>
                                        </div>
                                        <div class="ms-4 mt-1 small text-muted">
                                            Harga Standard: <span class="fw-bold text-dark font-mono">Rp {{ number_format($menu->price, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('store.backend.campaigns.index', $store->slug) }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-save me-1"></i> Perbarui Campaign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
