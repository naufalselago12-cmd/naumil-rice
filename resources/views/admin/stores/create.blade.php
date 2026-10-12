@extends('layouts.admin')

@section('title', 'Tambah Cabang Baru')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-custom">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-store me-2 text-primary"></i> Tambah Cabang / Toko Baru</h5>
                <a href="{{ route('admin.stores.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <form action="{{ route('admin.stores.store') }}" method="POST" enctype="multipart/form-data" class="p-4">
                @csrf

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Nama Toko / Cabang <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                               placeholder="Contoh: Naumil Rice - Tebet Kitchen" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Kode Toko <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control text-uppercase @error('code') is-invalid @enderror" 
                               placeholder="Contoh: NR-TBT" value="{{ old('code') }}" required>
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Alamat Lengkap Toko <span class="text-danger">*</span></label>
                        <textarea name="address" rows="3" class="form-control @error('address') is-invalid @enderror" 
                                  placeholder="Alamat fisik untuk persiapan dapur dan penjemputan pre-order..." required>{{ old('address') }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">No. Kontak / WhatsApp Toko</label>
                        <input type="text" name="phone" class="form-control" placeholder="08123456789" value="{{ old('phone') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Status Cabang</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Aktif (Menerima Pesanan)</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Non-Aktif (Tutup Sementara)</option>
                        </select>
                    </div>

                    <div class="col-12"><hr class="my-3"></div>

                    <!-- Logo Settings -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><i class="fa-solid fa-image me-1 text-info"></i> Logo Toko (File Upload)</label>
                        <input type="file" name="logo_file" class="form-control" accept="image/*">
                        <small class="text-muted d-block mt-1">Format: JPG, PNG, WEBP max 3MB.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Atau URL Logo Toko</label>
                        <input type="url" name="logo" class="form-control" placeholder="https://..." value="{{ old('logo') }}">
                    </div>

                    <div class="col-12"><hr class="my-3"></div>

                    <!-- Dynamic QRIS Settings -->
                    <div class="col-12">
                        <h6 class="fw-bold text-primary mb-2">
                            <i class="fa-solid fa-qrcode me-2"></i> Konfigurasi QRIS Dinamis Per Toko
                        </h6>
                        <p class="text-muted small">
                            QRIS ini akan ditampilkan kepada pelanggan saat memilih metode <strong>Bayar langsung QRIS Dinamis</strong> pada form Pre-Order.
                        </p>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Unggah Gambar QRIS Toko</label>
                        <input type="file" name="qris_file" class="form-control" accept="image/*">
                        <small class="text-muted d-block mt-1">Unggah barcode QRIS resmi dari bank/merchant cabang ini.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Atau URL Gambar QRIS</label>
                        <input type="url" name="qris_image" class="form-control" placeholder="https://..." value="{{ old('qris_image') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Data Payload / String QRIS (Opsional / EMVCo)</label>
                        <textarea name="qris_data" rows="2" class="form-control font-monospace" 
                                  placeholder="00020101021226600014ID.LINKAJA.WWW...">{{ old('qris_data') }}</textarea>
                        <small class="text-muted">Jika diisi, sistem dapat otomatis merender QR code dinamis bila file gambar belum diunggah.</small>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.stores.index') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-save me-1"></i> Simpan Cabang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
