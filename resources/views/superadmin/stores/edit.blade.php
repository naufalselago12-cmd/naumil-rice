@extends('superadmin.layouts.app')

@section('title', 'Edit Cabang ' . $store->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('superadmin.stores.index') }}" class="text-muted text-decoration-none small">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Daftar Cabang
    </a>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <div>
            <h2 class="fw-bold text-white mb-0">Edit Cabang: {{ $store->name }}</h2>
            <p class="text-muted mb-0">Ubah konfigurasi nama, slug URL, dan informasi QRIS cabang.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ url('/' . ($store->slug ?: $store->code)) }}" target="_blank" class="btn btn-outline-info btn-sm">
                <i class="fa-solid fa-globe me-1"></i> Buka Frontend
            </a>
            <a href="{{ url('/' . ($store->slug ?: $store->code) . '/backend') }}" target="_blank" class="btn btn-super btn-sm">
                <i class="fa-solid fa-gauge me-1"></i> Masuk Backend
            </a>
        </div>
    </div>
</div>

<div class="card-dark max-w-800">
    <div class="card-dark-header">
        <h5 class="card-dark-title"><i class="fa-solid fa-pen-to-square text-danger me-2"></i> Perbarui Data Cabang</h5>
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

        <form action="{{ route('superadmin.stores.update', $store->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row g-3 mb-3">
                <div class="col-md-7">
                    <label class="form-label-dark">Nama Cabang / Toko <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control form-control-dark" 
                           value="{{ old('name', $store->name) }}" required>
                </div>

                <div class="col-md-5">
                    <label class="form-label-dark">Kode Cabang (Unik) <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control form-control-dark text-uppercase font-mono" 
                           value="{{ old('code', $store->code) }}" required>
                </div>
            </div>

            <!-- Slug URL -->
            <div class="mb-3">
                <label class="form-label-dark">Slug URL Toko (Link: <code>{{ url('/') }}/<strong>{{ $store->slug }}</strong></code>) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary">{{ url('/') }}/</span>
                    <input type="text" name="slug" class="form-control form-control-dark font-mono text-info" 
                           value="{{ old('slug', $store->slug) }}" required>
                </div>
                <small class="text-muted">Slug menentukan URL halaman pembeli (<code>base_url/(nama_toko)</code>) dan URL backend (<code>base_url/(nama_toko)/backend</code>).</small>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label-dark">Nomor Telepon / WhatsApp Toko</label>
                    <input type="text" name="phone" class="form-control form-control-dark font-mono" 
                           value="{{ old('phone', $store->phone) }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label-dark">Status Operasional <span class="text-danger">*</span></label>
                    <select name="status" class="form-select form-select-dark" required>
                        <option value="active" {{ old('status', $store->status) === 'active' ? 'selected' : '' }}>Aktif (Menerima Pesanan)</option>
                        <option value="inactive" {{ old('status', $store->status) === 'inactive' ? 'selected' : '' }}>Nonaktif (Tutup Sementara)</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label-dark">Alamat Lengkap Cabang <span class="text-danger">*</span></label>
                <textarea name="address" rows="2" class="form-control form-control-dark" required>{{ old('address', $store->address) }}</textarea>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label-dark">URL Logo Toko</label>
                    <input type="url" name="logo" class="form-control form-control-dark" 
                           value="{{ old('logo', $store->logo) }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label-dark">Ganti File Logo (Upload Baru)</label>
                    <input type="file" name="logo_file" class="form-control form-control-dark" accept="image/*">
                </div>
            </div>

            <div class="p-3 rounded bg-black bg-opacity-40 border border-secondary mb-4">
                <h6 class="fw-bold text-white mb-2"><i class="fa-solid fa-qrcode text-primary me-2"></i> Konfigurasi QRIS Toko</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label-dark">QRIS String Payload (Data Merchant)</label>
                        <textarea name="qris_data" rows="2" class="form-control form-control-dark font-mono small">{{ old('qris_data', $store->qris_data) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-dark">Ganti File QRIS / URL</label>
                        <input type="file" name="qris_file" class="form-control form-control-dark mb-2" accept="image/*">
                        <input type="url" name="qris_image" class="form-control form-control-dark small" 
                               value="{{ old('qris_image', $store->qris_image) }}">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('superadmin.stores.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-super px-4">
                    <i class="fa-solid fa-save me-1"></i> Perbarui Cabang
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
