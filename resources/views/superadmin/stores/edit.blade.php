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

            <!-- Pilihan Metode Pembayaran Utama -->
            <div class="p-3 rounded bg-black bg-opacity-40 border border-secondary mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold text-white mb-0">
                        <i class="fa-solid fa-wallet text-warning me-2"></i> Pilihan Metode Pembayaran Toko
                    </h6>
                    <span class="badge bg-warning text-dark">Wajib Pilih Salah Satu</span>
                </div>

                @php
                    $curPaymentType = old('payment_type', $store->payment_type ?? 'qris_static');
                @endphp

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-3 rounded border border-secondary cursor-pointer bg-dark bg-opacity-50">
                            <input type="radio" name="payment_type" value="qris_static" class="form-check-input" 
                                   {{ $curPaymentType === 'qris_static' ? 'checked' : '' }}
                                   onchange="togglePaymentSectionEdit(this.value)">
                            <div>
                                <strong class="text-white d-block"><i class="fa-solid fa-qrcode text-primary me-1"></i> QRIS Statik Toko</strong>
                                <small class="text-muted">Pelanggan scan QRIS langsung dan upload bukti / konfirmasi manual.</small>
                            </div>
                        </label>
                    </div>

                    <div class="col-md-6">
                        <label class="d-flex align-items-center gap-2 p-3 rounded border border-secondary cursor-pointer bg-dark bg-opacity-50">
                            <input type="radio" name="payment_type" value="payment_gateway" class="form-check-input" 
                                   {{ $curPaymentType === 'payment_gateway' ? 'checked' : '' }}
                                   onchange="togglePaymentSectionEdit(this.value)">
                            <div>
                                <strong class="text-white d-block"><i class="fa-solid fa-bolt text-warning me-1"></i> Payment Gateway (Mayar.id)</strong>
                                <small class="text-muted">Pembayaran online instan via invoice Mayar.id (otomatis lunas).</small>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Bagian QRIS Statik -->
                <div id="edit_section_qris" class="{{ $curPaymentType === 'qris_static' ? '' : 'd-none' }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label-dark">QRIS String Payload (Data Merchant)</label>
                            <textarea name="qris_data" rows="2" class="form-control form-control-dark font-mono small">{{ old('qris_data', $store->qris_data) }}</textarea>
                            <small class="text-muted">Digunakan untuk menghasilkan QRIS dinamis per transaksi.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-dark">Ganti File QRIS / URL</label>
                            <input type="file" name="qris_file" class="form-control form-control-dark mb-2" accept="image/*">
                            <input type="url" name="qris_image" class="form-control form-control-dark small" 
                                   value="{{ old('qris_image', $store->qris_image) }}">
                            @if($store->qris_image)
                                <div class="mt-2">
                                    <img src="{{ $store->qris_image }}" alt="QRIS" class="rounded border border-secondary" style="height: 48px;">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Bagian Payment Gateway Mayar.id -->
                <div id="edit_section_mayar" class="{{ $curPaymentType === 'payment_gateway' ? '' : 'd-none' }}">
                    <div class="row g-3 mb-2">
                        <div class="col-md-4">
                            <label class="form-label-dark">Environment Mayar</label>
                            <select name="mayar_environment" class="form-select form-select-dark">
                                <option value="sandbox" {{ old('mayar_environment', $store->mayar_environment ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox (Testing)</option>
                                <option value="production" {{ old('mayar_environment', $store->mayar_environment) === 'production' ? 'selected' : '' }}>Production (Live)</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label-dark">API Key Mayar.id (Bearer Token)</label>
                            <input type="password" name="mayar_api_key" class="form-control form-control-dark font-mono small" 
                                   placeholder="API Key dari dashboard Mayar.id" value="{{ old('mayar_api_key', $store->mayar_api_key) }}">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label-dark">Webhook Token / Secret (Opsional)</label>
                        <input type="password" name="mayar_webhook_token" class="form-control form-control-dark font-mono small" 
                               placeholder="Token webhook rahasia" value="{{ old('mayar_webhook_token', $store->mayar_webhook_token) }}">
                    </div>
                    <div class="p-2 rounded bg-black bg-opacity-25 border border-secondary font-mono small text-muted">
                        Webhook Callback: <code>{{ url('/api/v1/payment/mayar/webhook/' . $store->slug) }}</code>
                    </div>
                </div>
            </div>

            <script>
            function togglePaymentSectionEdit(type) {
                const qris = document.getElementById('edit_section_qris');
                const mayar = document.getElementById('edit_section_mayar');
                if (type === 'qris_static') {
                    qris.classList.remove('d-none');
                    mayar.classList.add('d-none');
                } else {
                    mayar.classList.remove('d-none');
                    qris.classList.add('d-none');
                }
            }
            </script>

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
