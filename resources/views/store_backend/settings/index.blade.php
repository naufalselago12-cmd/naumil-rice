@extends('store_backend.layouts.app')

@section('title', 'Pengaturan Cabang - ' . $store->name)

@section('content')
<div class="mb-4">
    <h2 class="fw-bold mb-1 text-dark">Profil & QRIS Cabang: {{ $store->name }}</h2>
    <p class="text-muted mb-0">Atur kontak WhatsApp pemesanan, alamat operasional dapur, dan QRIS dinamis toko.</p>
</div>

<div class="card-custom max-w-800">
    <div class="card-custom-header">
        <h5 class="card-custom-title"><i class="fa-solid fa-store text-primary me-2"></i> Konfigurasi Cabang</h5>
        <span class="badge bg-danger font-mono">{{ $store->code }}</span>
    </div>

    <div class="p-4">
        @if($errors->any())
            <div class="alert alert-danger border-0 mb-4">
                <ul class="mb-0">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('store.backend.settings.update', $store->slug) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-bold">Alamat Lengkap Outlet / Dapur <span class="text-danger">*</span></label>
                <textarea name="address" rows="2" class="form-control" required>{{ old('address', $store->address) }}</textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nomor WhatsApp Cabang</label>
                    <input type="text" name="phone" class="form-control font-mono" 
                           placeholder="Contoh: 081298765432" value="{{ old('phone', $store->phone) }}">
                    <small class="text-muted">Nomor ini akan dihubungi oleh pelanggan di halaman toko.</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Logo Toko (Upload File)</label>
                    <input type="file" name="logo_file" class="form-control" accept="image/*">
                </div>
            </div>

            <div class="p-3 rounded bg-light border mb-4">
                <h6 class="fw-bold mb-2"><i class="fa-solid fa-qrcode text-primary me-2"></i> Pengaturan QRIS Dinamis Per Toko</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">QRIS String Payload (Data Merchant Resmi)</label>
                        <textarea name="qris_data" rows="3" class="form-control font-mono small">{{ old('qris_data', $store->qris_data) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Upload File Gambar QRIS</label>
                        <input type="file" name="qris_file" class="form-control mb-2" accept="image/*">
                        @if($store->qris_image)
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <img src="{{ $store->qris_image }}" alt="QRIS" class="border rounded" style="width: 50px; height: 50px;">
                                <small class="text-success fw-bold"><i class="fa-solid fa-check"></i> QRIS aktif</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fa-solid fa-save me-1"></i> Simpan Perubahan Profil Cabang
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
