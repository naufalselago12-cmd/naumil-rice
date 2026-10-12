@extends('layouts.admin')

@section('title', 'Tambah Menu Makanan Baru')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card-custom">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-bowl-food me-2 text-primary"></i> Tambah Menu Makanan Baru</h5>
                <a href="{{ route('admin.menus.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <form action="{{ route('admin.menus.store') }}" method="POST" enctype="multipart/form-data" class="p-4">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Cabang / Toko <span class="text-danger">*</span></label>
                        <select name="store_id" class="form-select @error('store_id') is-invalid @enderror" required>
                            @foreach($stores as $st)
                                <option value="{{ $st->id }}" {{ $selectedStoreId == $st->id ? 'selected' : '' }}>
                                    {{ $st->name }} ({{ $st->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('store_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Kategori Menu</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Tanpa Kategori / Umum --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold">Nama Menu <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                               placeholder="Contoh: Rice Bowl Ayam Suwir Kecombrang" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Harga Satuan (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control @error('price') is-invalid @enderror" 
                               placeholder="45000" value="{{ old('price') }}" required min="0">
                        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Deskripsi Menu & Komposisi</label>
                        <textarea name="description" rows="3" class="form-control" 
                                  placeholder="Deskripsi cita rasa, porsi nasi, lauk pelengkap, dan rekomendasi sambal...">{{ old('description') }}</textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Initial Stok Awal <span class="text-danger">*</span></label>
                        <input type="number" name="initial_stock" class="form-control" value="100" min="0" required>
                        <small class="text-muted">Jumlah porsi siap saji/kuota awal.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Unggah Foto Produk</label>
                        <input type="file" name="image_file" class="form-control" accept="image/*">
                        <small class="text-muted">JPG, PNG, WEBP max 3MB.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Atau URL Gambar Produk</label>
                        <input type="url" name="image_url" class="form-control" 
                               placeholder="https://images.unsplash.com/..." 
                               value="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80">
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" checked>
                            <label class="form-check-label fw-semibold" for="isActive">Menu Aktif (Dapat Dipesan)</label>
                        </div>
                    </div>

                    <!-- Repeater for Conditions, Toppings, Upsize -->
                    <div class="col-12"><hr class="my-4"></div>

                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold text-primary mb-1">
                                    <i class="fa-solid fa-list-check me-2"></i> Opsi Tambahan Produk (Topping, Upsize & Kondisi)
                                </h6>
                                <p class="text-muted small mb-0">
                                    Tambahkan opsi kustomisasi seperti Topping (cth: Telur Mata Sapi +Rp6.000), Upsize (cth: Jumbo +Rp12.000), atau Tingkat Pedas/Kondisi.
                                </p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addOptionBtn">
                                <i class="fa-solid fa-plus me-1"></i> Tambah Baris Opsi
                            </button>
                        </div>

                        <div class="table-responsive border rounded-3">
                            <table class="table table-bordered align-middle mb-0" id="optionsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 220px;">Tipe Opsi</th>
                                        <th>Nama Opsi / Label</th>
                                        <th style="width: 180px;">Biaya Tambahan (Rp)</th>
                                        <th style="width: 60px;" class="text-center">Hapus</th>
                                    </tr>
                                </thead>
                                <tbody id="optionsContainer">
                                    <!-- Default Option Row 1: Topping -->
                                    <tr>
                                        <td>
                                            <select name="options[0][type]" class="form-select form-select-sm">
                                                <option value="topping" selected>Topping (Extra Lauk/Telur)</option>
                                                <option value="upsize">Upsize (Porsi Jumbo)</option>
                                                <option value="condition">Kondisi (Level Pedas/Catatan)</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="options[0][name]" class="form-control form-select-sm" 
                                                   placeholder="Contoh: Telur Mata Sapi Setengah Matang" value="Telur Mata Sapi Setengah Matang">
                                        </td>
                                        <td>
                                            <input type="number" name="options[0][price]" class="form-control form-select-sm" 
                                                   placeholder="6000" value="6000" min="0">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-option-btn">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Default Option Row 2: Upsize -->
                                    <tr>
                                        <td>
                                            <select name="options[1][type]" class="form-select form-select-sm">
                                                <option value="topping">Topping (Extra Lauk/Telur)</option>
                                                <option value="upsize" selected>Upsize (Porsi Jumbo)</option>
                                                <option value="condition">Kondisi (Level Pedas/Catatan)</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="options[1][name]" class="form-control form-select-sm" 
                                                   placeholder="Contoh: Jumbo Size (Nasi + 30% Lauk)" value="Jumbo Size (Nasi + 30% Lauk)">
                                        </td>
                                        <td>
                                            <input type="number" name="options[1][price]" class="form-control form-select-sm" 
                                                   placeholder="12000" value="12000" min="0">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-option-btn">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.menus.index') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-save me-1"></i> Simpan Menu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let optionRowIndex = 2;
    document.getElementById('addOptionBtn').addEventListener('click', function() {
        const tbody = document.getElementById('optionsContainer');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <select name="options[${optionRowIndex}][type]" class="form-select form-select-sm">
                    <option value="topping">Topping (Extra Lauk/Telur)</option>
                    <option value="upsize">Upsize (Porsi Jumbo)</option>
                    <option value="condition">Kondisi (Level Pedas/Catatan)</option>
                </select>
            </td>
            <td>
                <input type="text" name="options[${optionRowIndex}][name]" class="form-control form-select-sm" 
                       placeholder="Contoh: Extra Sambal Matah" required>
            </td>
            <td>
                <input type="number" name="options[${optionRowIndex}][price]" class="form-control form-select-sm" 
                       placeholder="0" value="0" min="0">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-option-btn">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
        optionRowIndex++;
    });

    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-option-btn')) {
            const row = e.target.closest('tr');
            if (document.querySelectorAll('#optionsContainer tr').length > 1) {
                row.remove();
            } else {
                alert('Minimal sisakan satu baris opsi atau kosongkan input.');
            }
        }
    });
</script>
@endpush
