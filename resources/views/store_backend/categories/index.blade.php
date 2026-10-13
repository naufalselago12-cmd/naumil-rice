@extends('store_backend.layouts.app')

@section('title', 'Kategori Menu - ' . $store->name)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1 text-dark">Kategori Menu: {{ $store->name }}</h2>
        <p class="text-muted mb-0">Kelola pengelompokan menu masakan khusus cabang {{ $store->code }}.</p>
    </div>

    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="fa-solid fa-plus me-1"></i> Tambah Kategori Baru
    </button>
</div>

<div class="card-custom">
    <div class="card-custom-header">
        <h5 class="card-custom-title"><i class="fa-solid fa-layer-group text-primary me-2"></i> Daftar Kategori Cabang</h5>
        <span class="badge bg-secondary">{{ $categories->count() }} Kategori</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 80px;">Urutan</th>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Menu</th>
                    <th style="width: 120px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                    <tr>
                        <td class="font-mono fw-bold">#{{ $cat->display_order }}</td>
                        <td>
                            <strong class="fs-6 text-dark">{{ $cat->name }}</strong>
                        </td>
                        <td>
                            <small class="text-muted">{{ $cat->description ?: '-' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-info text-dark">{{ $cat->menus_count }} Menu Terkait</span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        data-bs-toggle="modal" data-bs-target="#editModal{{ $cat->id }}">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('store.backend.categories.destroy', ['store_slug' => $store->slug, 'category' => $cat->id]) }}" 
                                      method="POST" onsubmit="return confirm('Hapus kategori {{ $cat->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>

                            <!-- Edit Modal -->
                            <div class="modal fade text-start" id="editModal{{ $cat->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('store.backend.categories.update', ['store_slug' => $store->slug, 'category' => $cat->id]) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Kategori</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Nama Kategori</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $cat->name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Urutan Tampil</label>
                                                    <input type="number" name="display_order" class="form-control" value="{{ $cat->display_order }}" min="0">
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
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">Belum ada kategori menu untuk cabang ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add Category -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('store.backend.categories.store', $store->slug) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kategori Baru ({{ $store->name }})</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Signature Rice Bowls" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Urutan Tampil</label>
                        <input type="number" name="display_order" class="form-control" value="1" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi Singkat</label>
                        <textarea name="description" rows="2" class="form-control" placeholder="Deskripsi pengelompokan menu..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
