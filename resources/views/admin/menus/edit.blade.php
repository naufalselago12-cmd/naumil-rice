@extends('layouts.admin')

@section('title', 'Edit Menu ' . $menu->name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card-custom">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Edit Menu Makanan & Opsi</h5>
                <a href="{{ route('admin.menus.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <form action="{{ route('admin.menus.update', $menu->id) }}" method="POST" enctype="multipart/form-data" class="p-4">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Cabang / Toko <span class="text-danger">*</span></label>
                        <select name="store_id" class="form-select @error('store_id') is-invalid @enderror" required>
                            @foreach($stores as $st)
                                <option value="{{ $st->id }}" {{ $menu->store_id == $st->id ? 'selected' : '' }}>
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
                                <option value="{{ $cat->id }}" {{ $menu->category_id == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold">Nama Menu <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                               value="{{ old('name', $menu->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Harga Satuan (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control @error('price') is-invalid @enderror" 
                               value="{{ old('price', $menu->price) }}" required min="0">
                        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Deskripsi Menu & Komposisi</label>
                        <textarea name="description" rows="3" class="form-control">{{ old('description', $menu->description) }}</textarea>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Initial Stok Awal</label>
                        <input type="number" name="initial_stock" class="form-control" value="{{ old('initial_stock', $menu->initial_stock) }}" min="0" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Stok Sisa Saat Ini</label>
                        <input type="number" name="current_stock" class="form-control" value="{{ old('current_stock', $menu->current_stock) }}" min="0" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Unggah Foto Produk Baru</label>
                        <input type="file" name="image_file" class="form-control" accept="image/*">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Atau URL Gambar</label>
                        <input type="url" name="image_url" class="form-control" value="{{ old('image_url', $menu->image) }}">
                    </div>

                    @if($menu->image)
                        <div class="col-12">
                            <label class="form-label fw-bold d-block">Preview Foto Saat Ini:</label>
                            <img src="{{ $menu->image }}" alt="{{ $menu->name }}" class="rounded-3 border" style="width: 100px; height: 100px; object-fit: cover;">
                        </div>
                    @endif

                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ $menu->is_active ? 'checked' : '' }}>
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
                                <p class="text-muted small mb-0">Kelola topping tambahan, upsize porsi, atau level pedas/kondisi.</p>
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
                                    @forelse($menu->options as $index => $opt)
                                        <tr>
                                            <td>
                                                <select name="options[{{ $index }}][type]" class="form-select form-select-sm">
                                                    <option value="topping" {{ $opt->type === 'topping' ? 'selected' : '' }}>Topping (Extra Lauk/Telur)</option>
                                                    <option value="upsize" {{ $opt->type === 'upsize' ? 'selected' : '' }}>Upsize (Porsi Jumbo)</option>
                                                    <option value="condition" {{ $opt->type === 'condition' ? 'selected' : '' }}>Kondisi (Level Pedas/Catatan)</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="options[{{ $index }}][name]" class="form-control form-select-sm" 
                                                       value="{{ $opt->name }}" required>
                                            </td>
                                            <td>
                                                <input type="number" name="options[{{ $index }}][price]" class="form-control form-select-sm" 
                                                       value="{{ $opt->price }}" min="0">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-option-btn">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td>
                                                <select name="options[0][type]" class="form-select form-select-sm">
                                                    <option value="topping">Topping (Extra Lauk/Telur)</option>
                                                    <option value="upsize">Upsize (Porsi Jumbo)</option>
                                                    <option value="condition">Kondisi (Level Pedas/Catatan)</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="options[0][name]" class="form-control form-select-sm" 
                                                       placeholder="Contoh: Telur Mata Sapi">
                                            </td>
                                            <td>
                                                <input type="number" name="options[0][price]" class="form-control form-select-sm" 
                                                       value="0" min="0">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-option-btn">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.menus.index') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-save me-1"></i> Perbarui Menu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let optionRowIndex = {{ max(1, count($menu->options)) }};
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
