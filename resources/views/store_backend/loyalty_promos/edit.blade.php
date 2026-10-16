@extends('store_backend.layouts.app')

@section('title', 'Edit Promo Pelanggan Setia - ' . $promo->title)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card-custom">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Edit Program Promo: {{ $promo->title }} (Cabang: {{ $store->name }})</h5>
                <a href="{{ route('store.backend.loyaltyPromos.index', $store->slug) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <form action="{{ route('store.backend.loyaltyPromos.update', ['store_slug' => $store->slug, 'promo' => $promo->id]) }}" method="POST" class="p-4">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Judul / Nama Promo <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $promo->title) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Status Promo</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ old('is_active', $promo->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="isActive">Promo Aktif</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Deskripsi / Ketentuan Promo (Opsional)</label>
                        <textarea name="description" rows="2" class="form-control">{{ old('description', $promo->description) }}</textarea>
                    </div>

                    <!-- Tipe Promo Selector -->
                    <div class="col-12 pt-3 border-top">
                        <label class="form-label fw-bold fs-6 mb-2">
                            <i class="fa-solid fa-sliders text-primary me-2"></i> Pilih Skema Diskon Promo <span class="text-danger">*</span>
                        </label>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="p-3 border rounded h-100 d-block cursor-pointer bg-light promo-type-card" style="cursor: pointer;">
                                    <input type="radio" name="promo_type" value="all_menu_percent" {{ old('promo_type', $promo->promo_type) === 'all_menu_percent' ? 'checked' : '' }} onchange="togglePromoType()">
                                    <div class="fw-bold mt-2 text-primary"><i class="fa-solid fa-border-all me-1"></i> Persentase Semua Menu</div>
                                    <small class="text-muted d-block mt-1">Diskon sekian % berlaku merata untuk seluruh menu makanan di cabang ini.</small>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="p-3 border rounded h-100 d-block cursor-pointer bg-light promo-type-card" style="cursor: pointer;">
                                    <input type="radio" name="promo_type" value="per_menu" {{ old('promo_type', $promo->promo_type) === 'per_menu' ? 'checked' : '' }} onchange="togglePromoType()">
                                    <div class="fw-bold mt-2 text-info"><i class="fa-solid fa-bowl-food me-1"></i> Khusus Per Menu</div>
                                    <small class="text-muted d-block mt-1">Tentukan potongan harga atau persentase khusus pada menu-menu tertentu.</small>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="p-3 border rounded h-100 d-block cursor-pointer bg-light promo-type-card" style="cursor: pointer;">
                                    <input type="radio" name="promo_type" value="min_purchase_discount" {{ old('promo_type', $promo->promo_type) === 'min_purchase_discount' ? 'checked' : '' }} onchange="togglePromoType()">
                                    <div class="fw-bold mt-2 text-success"><i class="fa-solid fa-money-bill-wave me-1"></i> Potongan Min. Belanja</div>
                                    <small class="text-muted d-block mt-1">Potongan nominal tetap (Rp) dengan syarat minimum nilai transaksi.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Config Type 1: All Menu Percent -->
                    <div class="col-12" id="boxAllMenuPercent">
                        <div class="p-3 rounded bg-light border">
                            <label class="form-label fw-bold">Persentase Diskon Seluruh Menu (%) <span class="text-danger">*</span></label>
                            <div class="input-group" style="max-width: 250px;">
                                <input type="number" name="discount_percent" class="form-control" value="{{ old('discount_percent', $promo->discount_percent) }}" min="1" max="100" step="0.5">
                                <span class="input-group-text font-mono fw-bold">% OFF</span>
                            </div>
                            <small class="text-muted">Setiap item yang dimasukkan ke keranjang akan otomatis mendapat potongan sebesar persentase ini.</small>
                        </div>
                    </div>

                    <!-- Config Type 2: Per Menu -->
                    <div class="col-12 d-none" id="boxPerMenu">
                        <div class="p-3 rounded bg-light border">
                            <div class="fw-bold mb-2">Pilih Menu & Atur Potongan Per Menu:</div>
                            <small class="text-muted d-block mb-3">Centang menu yang ingin diikutsertakan dalam promo dan tentukan potongan (% atau Rp nominal).</small>

                            @php
                                $selectedMenuIds = $promo->menus->pluck('id')->toArray();
                            @endphp

                            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                <table class="table table-sm table-bordered bg-white align-middle mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="width: 40px;">Pilih</th>
                                            <th>Nama Menu</th>
                                            <th>Harga Normal</th>
                                            <th>Diskon (%)</th>
                                            <th>Atau Potongan (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($menus as $idx => $m)
                                            @php
                                                $pivot = $promo->menus->where('id', $m->id)->first()?->pivot;
                                                $isChecked = in_array($m->id, $selectedMenuIds);
                                            @endphp
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" name="menus[{{ $idx }}][id]" value="{{ $m->id }}" id="menuCheck_{{ $m->id }}" class="form-check-input" {{ $isChecked ? 'checked' : '' }}>
                                                </td>
                                                <td>
                                                    <label for="menuCheck_{{ $m->id }}" class="fw-bold cursor-pointer mb-0">{{ $m->name }}</label>
                                                </td>
                                                <td class="font-mono text-muted">Rp {{ number_format($m->price, 0, ',', '.') }}</td>
                                                <td style="width: 140px;">
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="menus[{{ $idx }}][discount_percent]" class="form-control" value="{{ $pivot ? $pivot->discount_percent : '' }}" placeholder="0" min="0" max="100">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </td>
                                                <td style="width: 180px;">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="number" name="menus[{{ $idx }}][discount_amount]" class="form-control" value="{{ $pivot ? $pivot->discount_amount : '' }}" placeholder="0" min="0">
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Config Type 3: Min Purchase Discount -->
                    <div class="col-12 d-none" id="boxMinPurchase">
                        <div class="p-3 rounded bg-light border">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Nomor Potongan Diskon (Rp) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="discount_amount" class="form-control" value="{{ old('discount_amount', $promo->discount_amount) }}" min="0">
                                    </div>
                                    <small class="text-muted">Nominal potongan harga yang langsung dipotong dari total belanja.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Syarat Minimum Pembelian (Rp) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="min_purchase_amount" class="form-control" value="{{ old('min_purchase_amount', $promo->min_purchase_amount) }}" min="0">
                                    </div>
                                    <small class="text-muted">Diskon hanya berlaku jika subtotal belanja pelanggan mencapai jumlah ini.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Benefit Choice Config: Diskon vs Poin Lebih Banyak -->
                    <div class="col-12 pt-3 border-top">
                        <div class="p-3 rounded bg-warning-subtle border border-warning">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="allow_benefit_choice" id="allowChoice" value="1" {{ old('allow_benefit_choice', $promo->allow_benefit_choice) ? 'checked' : '' }} onchange="toggleChoiceFields()">
                                <label class="form-check-label fw-bold text-dark" for="allowChoice">
                                    <i class="fa-solid fa-code-compare text-warning me-1"></i> Izinkan Pelanggan Setia Memilih: Mau Pakai Potongan Diskon ATAU Mau Poin Lebih Banyak
                                </label>
                            </div>
                            <p class="small text-muted mb-3">
                                Jika diaktifkan, saat checkout pelanggan setia dapat memilih apakah ingin menggunakan potongan harga diskon tunai ATAU memilih mengalihkan promo menjadi bonus credit point ekstra yang berlipat ganda!
                            </p>

                            <div id="choiceSettingsBox" class="row g-3 pt-2 border-top">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Multiplier Poin Hadiah (Kelipatan Poin)</label>
                                    <div class="input-group">
                                        <input type="number" name="extra_points_multiplier" class="form-control" value="{{ old('extra_points_multiplier', $promo->extra_points_multiplier) }}" min="1" max="10" step="0.5">
                                        <span class="input-group-text font-mono">x Lipat Poin</span>
                                    </div>
                                    <small class="text-muted">Contoh: 2x lipat poin (Double Point Reward jika pelanggan memilih opsi Poin).</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Bonus Poin Tambahan Flat (Opsional)</label>
                                    <div class="input-group">
                                        <input type="number" name="extra_points_bonus" class="form-control" value="{{ old('extra_points_bonus', $promo->extra_points_bonus) }}" min="0">
                                        <span class="input-group-text font-mono">+ Pts Ekstra</span>
                                    </div>
                                    <small class="text-muted">Bonus poin langsung ditambahkan jika memilih opsi Poin.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Periode Promo -->
                    <div class="col-md-6 pt-3 border-top">
                        <label class="form-label fw-bold">Tanggal Mulai Berlaku (Opsional)</label>
                        <input type="datetime-local" name="start_date" class="form-control" value="{{ old('start_date', $promo->start_date ? $promo->start_date->format('Y-m-d\TH:i') : '') }}">
                        <small class="text-muted">Biarkan kosong jika langsung berlaku sekarang.</small>
                    </div>

                    <div class="col-md-6 pt-3 border-top">
                        <label class="form-label fw-bold">Tanggal Berakhir (Opsional)</label>
                        <input type="datetime-local" name="end_date" class="form-control" value="{{ old('end_date', $promo->end_date ? $promo->end_date->format('Y-m-d\TH:i') : '') }}">
                        <small class="text-muted">Biarkan kosong jika berlaku seterusnya tanpa batas waktu.</small>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('store.backend.loyaltyPromos.index', $store->slug) }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-save me-1"></i> Simpan Perubahan Promo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function togglePromoType() {
    const selected = document.querySelector('input[name="promo_type"]:checked')?.value || 'all_menu_percent';
    document.getElementById('boxAllMenuPercent').classList.toggle('d-none', selected !== 'all_menu_percent');
    document.getElementById('boxPerMenu').classList.toggle('d-none', selected !== 'per_menu');
    document.getElementById('boxMinPurchase').classList.toggle('d-none', selected !== 'min_purchase_discount');
}

function toggleChoiceFields() {
    const enabled = document.getElementById('allowChoice').checked;
    document.getElementById('choiceSettingsBox').classList.toggle('d-none', !enabled);
}

document.addEventListener('DOMContentLoaded', () => {
    togglePromoType();
    toggleChoiceFields();
});
</script>
@endsection
