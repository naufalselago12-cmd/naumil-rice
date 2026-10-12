@extends('layouts.admin')

@section('title', 'Edit Cabang ' . $store->name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-custom">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Edit Cabang & QRIS Dinamis</h5>
                <a href="{{ route('admin.stores.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <form action="{{ route('admin.stores.update', $store->id) }}" method="POST" enctype="multipart/form-data" class="p-4">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Nama Toko / Cabang <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                               value="{{ old('name', $store->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Kode Toko <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control text-uppercase @error('code') is-invalid @enderror" 
                               value="{{ old('code', $store->code) }}" required>
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Alamat Lengkap Toko <span class="text-danger">*</span></label>
                        <textarea name="address" rows="3" class="form-control @error('address') is-invalid @enderror" required>{{ old('address', $store->address) }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">No. Kontak / WhatsApp Toko</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $store->phone) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Status Cabang</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ old('status', $store->status) === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ old('status', $store->status) === 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                        </select>
                    </div>

                    <div class="col-12"><hr class="my-3"></div>

                    <!-- Logo Settings -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><i class="fa-solid fa-image me-1 text-info"></i> Logo Toko</label>
                        @if($store->logo)
                            <div class="mb-2">
                                <img src="{{ $store->logo }}" alt="Logo" class="rounded border" style="width: 70px; height: 70px; object-fit: cover;">
                            </div>
                        @endif
                        <input type="file" name="logo_file" class="form-control" accept="image/*">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Atau URL Logo Toko</label>
                        <input type="url" name="logo" class="form-control" value="{{ old('logo', $store->logo) }}">
                    </div>

                    <div class="col-12"><hr class="my-3"></div>

                    <!-- Dynamic QRIS Settings -->
                    <div class="col-12">
                        <h6 class="fw-bold text-primary mb-2">
                            <i class="fa-solid fa-qrcode me-2"></i> Setting QRIS Dinamis Per Toko
                        </h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Preview / Gambar QRIS</label>
                        @if($store->qris_image)
                            <div class="mb-2">
                                <img src="{{ $store->qris_image }}" alt="QRIS" class="rounded border p-1 bg-white" style="width: 120px; height: 120px; object-fit: contain;">
                            </div>
                        @endif
                        <input type="file" name="qris_file" class="form-control" accept="image/*">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Atau URL Gambar QRIS</label>
                        <input type="url" name="qris_image" class="form-control" value="{{ old('qris_image', $store->qris_image) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Data Payload / String QRIS</label>
                        <textarea name="qris_data" rows="2" class="form-control font-monospace">{{ old('qris_data', $store->qris_data) }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.stores.index') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-save me-1"></i> Perbarui Cabang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
