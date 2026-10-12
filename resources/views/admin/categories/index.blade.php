@extends('layouts.admin')

@section('title', 'Kategori Menu Per Toko')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1">Kategori Menu Per Toko</h2>
        <p class="text-muted mb-0">Kelola pengelompokan menu (Rice Bowl, Nasi Goreng, Sides, Minuman) untuk setiap cabang.</p>
    </div>

    <div class="d-flex align-items-center gap-2">
        <!-- Store Selector Filter -->
        <form method="GET" action="{{ route('admin.categories.index') }}" class="d-flex align-items-center gap-2">
            <select name="store_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 200px;">
                @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ $selectedStoreId == $st->id ? 'selected' : '' }}>
                        {{ $st->name }}
                    </option>
                @endforeach
            </select>
        </form>

        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fa-solid fa-plus me-1"></i> Tambah Kategori
        </button>
    </div>
</div>

<div class="card-custom">
    <div class="card-custom-header">
        <h5 class="card-custom-title"><i class="fa-solid fa-layer-group me-2 text-primary"></i> Daftar Kategori</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 80px;">Urutan</th>
                    <th>Nama Kategori</th>
                    <th>Cabang</th>
                    <th>Deskripsi</th>
                    <th style="width: 140px;">Jumlah Menu</th>
                    <th style="width: 120px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                    <tr>
                        <td>
                            <span class="badge bg-secondary">#{{ $cat->display_order }}</span>
                        </td>
                        <td class="fw-bold fs-6">{{ $cat->name }}</td>
                        <td>
                            <span class="badge bg-info text-dark">{{ $cat->store->code }}</span>
                            <small class="text-muted d-block">{{ $cat->store->name }}</small>
                        </td>
                        <td class="text-muted">{{ $cat->description ?: '-' }}</td>
                        <td>
                            <span class="badge bg-primary fs-7">{{ $cat->menus_count }} Item Menu</span>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    data-bs-toggle="modal" data-bs-target="#editModal{{ $cat->id }}">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <form action="{{ route('admin.categories.destroy', $cat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kategori ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal{{ $cat->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">Edit Kategori</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="{{ route('admin.categories.update', $cat->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Nama Kategori</label>
                                            <input type="text" name="name" class="form-control" value="{{ $cat->name }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Urutan Tampil (Display Order)</label>
                                            <input type="number" name="display_order" class="form-control" value="{{ $cat->display_order }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Deskripsi</label>
                                            <textarea name="description" rows="2" class="form-control">{{ $cat->description }}</textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada kategori untuk cabang ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Tambah Kategori Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.categories.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Cabang / Toko</label>
                        <select name="store_id" class="form-select" required>
                            @foreach($stores as $st)
                                <option value="{{ $st->id }}" {{ $selectedStoreId == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Kategori</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Signature Rice Bowls" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Urutan Tampil (Display Order)</label>
                        <input type="number" name="display_order" class="form-control" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi Kategori</label>
                        <textarea name="description" rows="2" class="form-control" placeholder="Deskripsi singkat..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
