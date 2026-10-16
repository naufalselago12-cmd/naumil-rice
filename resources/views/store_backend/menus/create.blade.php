@extends('store_backend.layouts.app')

@section('title', 'Tambah Menu Baru - ' . $store->name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card-custom">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-bowl-food me-2 text-primary"></i> Tambah Menu Makanan Baru (Cabang: {{ $store->name }})</h5>
                <a href="{{ route('store.backend.menus.index', $store->slug) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <form action="{{ route('store.backend.menus.store', $store->slug) }}" method="POST" enctype="multipart/form-data" class="p-4">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Kategori Menu</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Tanpa Kategori / Umum --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Status Produk</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="isActive">Tampilkan di Menu Pembeli</label>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold">Nama Menu Makanan <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" 
                               placeholder="Contoh: Rice Bowl Cumi Hitam Madura" value="{{ old('name') }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Harga Standard (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control" 
                               placeholder="45000" value="{{ old('price') }}" required min="0">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Deskripsi Menu & Komposisi</label>
                        <textarea name="description" rows="3" class="form-control" 
                                  placeholder="Deskripsi cita rasa, aroma bumbu, porsi nasi daun jeruk, dll...">{{ old('description') }}</textarea>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Stok Awal Porsi <span class="text-danger">*</span></label>
                        <input type="number" name="initial_stock" class="form-control" value="100" min="0" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">
                            <i class="fa-solid fa-coins text-warning me-1"></i> Reward Poin (Opsional)
                        </label>
                        <div class="input-group">
                            <input type="number" name="reward_points" class="form-control" placeholder="0" value="{{ old('reward_points', 0) }}" min="0">
                            <span class="input-group-text font-mono small">Pts</span>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Unggah Foto Makanan</label>
                        <input type="file" name="image_file" class="form-control" accept="image/*">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Atau URL Gambar</label>
                        <input type="url" name="image_url" class="form-control" 
                               placeholder="https://images.unsplash.com/..." 
                               value="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80">
                    </div>

                    <!-- Topping, Upsize, Conditions Section -->
                    <div class="col-12 mt-4 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold mb-0"><i class="fa-solid fa-list-check me-2 text-primary"></i> Varian, Topping & Upsize</h6>
                                <small class="text-muted">Pilihan tambahan yang dapat dipilih pelanggan saat memesan menu ini.</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addOptionBtn">
                                <i class="fa-solid fa-plus me-1"></i> Tambah Opsi
                            </button>
                        </div>

                        <div id="optionsContainer">
                            <!-- Option Row Sample 1 -->
                            <div class="row g-2 align-items-center mb-2 option-row p-2 rounded bg-light border">
                                <div class="col-md-3">
                                    <select name="options[0][type]" class="form-select form-select-sm">
                                        <option value="topping">Topping Tambahan</option>
                                        <option value="upsize">Porsi / Upsize</option>
                                        <option value="condition">Kondisi / Pedas</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <input type="text" name="options[0][name]" class="form-control form-control-sm" placeholder="Contoh: Telur Mata Sapi" value="Telur Mata Sapi">
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="options[0][price]" class="form-control" placeholder="6000" value="6000" min="0">
                                    </div>
                                </div>
                                <div class="col-md-1 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-option-btn"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('store.backend.menus.index', $store->slug) }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-save me-1"></i> Simpan Menu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let optIndex = 1;
document.getElementById('addOptionBtn').addEventListener('click', function() {
    const container = document.getElementById('optionsContainer');
    const row = document.createElement('div');
    row.className = 'row g-2 align-items-center mb-2 option-row p-2 rounded bg-light border';
    row.innerHTML = `
        <div class="col-md-3">
            <select name="options[${optIndex}][type]" class="form-select form-select-sm">
                <option value="topping">Topping Tambahan</option>
                <option value="upsize">Porsi / Upsize</option>
                <option value="condition">Kondisi / Pedas</option>
            </select>
        </div>
        <div class="col-md-5">
            <input type="text" name="options[${optIndex}][name]" class="form-control form-control-sm" placeholder="Nama varian / opsi">
        </div>
        <div class="col-md-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text">Rp</span>
                <input type="number" name="options[${optIndex}][price]" class="form-control" placeholder="0" value="0" min="0">
            </div>
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger remove-option-btn"><i class="fa-solid fa-xmark"></i></button>
        </div>
    `;
    container.appendChild(row);
    optIndex++;
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-option-btn')) {
        e.target.closest('.option-row').remove();
    }
});
</script>
@endpush
@endsection
