@extends('superadmin.layouts.app')

@section('title', 'Tambah Cabang Baru')

@section('content')
<div class="mb-4">
    <a href="{{ route('superadmin.stores.index') }}" class="text-muted text-decoration-none small">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Daftar Cabang
    </a>
    <h2 class="fw-bold mt-2 text-white">Tambah Cabang Toko Baru</h2>
    <p class="text-muted mb-0">Cabang baru akan memiliki URL frontend <code>base_url/(slug)</code> dan backend mandiri <code>base_url/(slug)/backend</code>.</p>
</div>

<div class="card-dark max-w-800">
    <div class="card-dark-header">
        <h5 class="card-dark-title"><i class="fa-solid fa-plus-circle text-danger me-2"></i> Formulir Registrasi Cabang Toko</h5>
    </div>

    <div class="p-4">
        @if($errors->any())
            <div class="alert alert-danger border-0 mb-4" style="background-color: rgba(244, 63, 94, 0.2); color: #fecdd3;">
                <ul class="mb-0">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('superadmin.stores.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-3 mb-3">
                <div class="col-md-7">
                    <label class="form-label-dark">Nama Cabang / Toko <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="storeName" class="form-control form-control-dark" 
                           placeholder="Contoh: Naumil Rice - Flagship SCBD" value="{{ old('name') }}" required
                           onkeyup="generateSlug(this.value)">
                </div>

                <div class="col-md-5">
                    <label class="form-label-dark">Kode Cabang (Unik) <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control form-control-dark text-uppercase font-mono" 
                           placeholder="Contoh: NR-SCBD" value="{{ old('code') }}" required>
                </div>
            </div>

            <!-- Slug URL -->
            <div class="mb-3">
                <label class="form-label-dark">
                    Slug URL Toko (Akan jadi: <code>{{ url('/') }}/<strong class="text-info" id="slugPreview">nama-toko</strong></code>)
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary">{{ url('/') }}/</span>
                    <input type="text" name="slug" id="storeSlug" class="form-control form-control-dark font-mono text-info" 
                           placeholder="nama-toko" value="{{ old('slug') }}">
                </div>
                <small class="text-muted">Biarkan kosong untuk membuat otomatis dari nama toko. Gunakan huruf kecil dan tanda hubung (-).</small>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label-dark">Nomor Telepon / WhatsApp Toko</label>
                    <input type="text" name="phone" class="form-control form-control-dark font-mono" 
                           placeholder="Contoh: 081298765432" value="{{ old('phone') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label-dark">Status Operasional <span class="text-danger">*</span></label>
                    <select name="status" class="form-select form-select-dark" required>
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Aktif (Menerima Pesanan)</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Nonaktif (Tutup Sementara)</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label-dark">Alamat Lengkap Cabang <span class="text-danger">*</span></label>
                <textarea name="address" rows="2" class="form-control form-control-dark" 
                          placeholder="Masukkan alamat dapur / outlet cabang ini" required>{{ old('address') }}</textarea>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label-dark">URL Logo Toko (Opsional)</label>
                    <input type="url" name="logo" class="form-control form-control-dark" 
                           placeholder="https://images.unsplash.com/..." value="{{ old('logo') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label-dark">Upload File Logo (Opsional)</label>
                    <input type="file" name="logo_file" class="form-control form-control-dark" accept="image/*">
                </div>
            </div>

            <div class="p-3 rounded bg-black bg-opacity-40 border border-secondary mb-4">
                <h6 class="fw-bold text-white mb-2"><i class="fa-solid fa-qrcode text-primary me-2"></i> Konfigurasi QRIS Dinamis Per Toko</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label-dark">QRIS String Payload (Data Merchant)</label>
                        <textarea name="qris_data" rows="2" class="form-control form-control-dark font-mono small" 
                                  placeholder="00020101021226600014ID.LINKAJA.WWW0118936009180000...">{{ old('qris_data') }}</textarea>
                        <small class="text-muted">Digunakan untuk menghasilkan QRIS dinamis unik per transaksi.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-dark">Upload Gambar QRIS Statis</label>
                        <input type="file" name="qris_file" class="form-control form-control-dark mb-2" accept="image/*">
                        <input type="url" name="qris_image" class="form-control form-control-dark small" 
                               placeholder="Atau URL gambar QRIS..." value="{{ old('qris_image') }}">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('superadmin.stores.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-super px-4">
                    <i class="fa-solid fa-save me-1"></i> Simpan Cabang Baru
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function generateSlug(text) {
    const slugInput = document.getElementById('storeSlug');
    const slug = text.toLowerCase().trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
    slugInput.value = slug;
    document.getElementById('slugPreview').textContent = slug || 'nama-toko';
}
document.getElementById('storeSlug').addEventListener('input', function() {
    document.getElementById('slugPreview').textContent = this.value || 'nama-toko';
});
</script>
@endpush
@endsection
