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
                        <div class="col-md-7">
                            <label class="form-label-dark">Upload File Gambar QRIS Statik Toko</label>
                            <input type="file" name="qris_file" class="form-control form-control-dark mb-2" accept="image/*">
                            <small class="text-muted d-block">Upload gambar QRIS toko resmi (JPG/PNG/WEBP) untuk ditampilkan langsung ke pelanggan.</small>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label-dark">Pratinjau QRIS Aktif</label>
                            @if($store->qris_image)
                                <div class="p-2 bg-dark rounded border border-secondary text-center">
                                    <img src="{{ $store->qris_image }}" alt="QRIS" class="rounded border border-secondary mb-1" style="max-height: 80px;">
                                    <small class="text-success d-block"><i class="fa-solid fa-circle-check"></i> QRIS Tersimpan</small>
                                </div>
                            @else
                                <div class="p-2 bg-dark rounded border border-secondary text-center text-muted">
                                    <small>Belum ada gambar QRIS</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Bagian Payment Gateway Mayar.id -->
                <div id="edit_section_mayar" class="{{ $curPaymentType === 'payment_gateway' ? '' : 'd-none' }}">
                    @php
                        $superCurEnv = old('mayar_environment', $store->mayar_environment ?? 'sandbox');
                    @endphp
                    <!-- Mode Environment Radio Buttons -->
                    <div class="p-3 rounded bg-black bg-opacity-25 border border-secondary mb-3">
                        <label class="form-label-dark d-block mb-2 fw-bold">
                            <i class="fa-solid fa-tower-broadcast text-info me-1"></i> Mode Lingkungan Mayar (Environment) <span class="text-danger">*</span>
                        </label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="p-2 rounded border border-secondary d-flex align-items-center gap-2 cursor-pointer w-100 {{ $superCurEnv === 'sandbox' ? 'bg-warning bg-opacity-10 border-warning text-white' : 'bg-dark text-muted' }}" for="sa_env_sandbox">
                                    <input type="radio" name="mayar_environment" id="sa_env_sandbox" value="sandbox" class="form-check-input mt-0" {{ $superCurEnv === 'sandbox' ? 'checked' : '' }}>
                                    <div>
                                        <div class="fw-bold small text-warning"><i class="fa-solid fa-flask me-1"></i> Sandbox (Testing)</div>
                                        <div class="text-xs text-muted" style="font-size: 11px;">Simulasi pembayaran tanpa uang asli</div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="p-2 rounded border border-secondary d-flex align-items-center gap-2 cursor-pointer w-100 {{ $superCurEnv === 'production' ? 'bg-success bg-opacity-10 border-success text-white' : 'bg-dark text-muted' }}" for="sa_env_production">
                                    <input type="radio" name="mayar_environment" id="sa_env_production" value="production" class="form-check-input mt-0" {{ $superCurEnv === 'production' ? 'checked' : '' }}>
                                    <div>
                                        <div class="fw-bold small text-success"><i class="fa-solid fa-circle-check me-1"></i> Production (Live)</div>
                                        <div class="text-xs text-muted" style="font-size: 11px;">Pembayaran riil dari customer</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Kredensial Sandbox & Production Disediakan Berdampingan -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded bg-black bg-opacity-25 border border-secondary h-100">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <strong class="text-warning small"><i class="fa-solid fa-flask me-1"></i> Kredensial Sandbox</strong>
                                    <span class="badge {{ $superCurEnv === 'sandbox' ? 'bg-warning text-dark' : 'bg-secondary' }}" style="font-size: 10px;">
                                        {{ $superCurEnv === 'sandbox' ? 'Aktif' : 'Standby' }}
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label-dark" style="font-size: 12px;">API Key Sandbox</label>
                                    <input type="password" name="mayar_api_key" class="form-control form-control-dark font-mono small" 
                                           placeholder="API Key Sandbox (eyJhbGci...)" value="{{ old('mayar_api_key', $store->mayar_api_key) }}">
                                </div>
                                <div>
                                    <label class="form-label-dark" style="font-size: 12px;">Webhook Token Sandbox</label>
                                    <input type="password" name="mayar_webhook_token" class="form-control form-control-dark font-mono small" 
                                           placeholder="Token webhook sandbox" value="{{ old('mayar_webhook_token', $store->mayar_webhook_token) }}">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 rounded bg-black bg-opacity-25 border border-secondary h-100">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <strong class="text-success small"><i class="fa-solid fa-circle-check me-1"></i> Kredensial Production</strong>
                                    <span class="badge {{ $superCurEnv === 'production' ? 'bg-success' : 'bg-secondary' }}" style="font-size: 10px;">
                                        {{ $superCurEnv === 'production' ? 'Aktif' : 'Standby' }}
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label-dark" style="font-size: 12px;">API Key Production</label>
                                    <input type="password" name="mayar_api_key_production" class="form-control form-control-dark font-mono small" 
                                           placeholder="API Key Production (eyJhbGci...)" value="{{ old('mayar_api_key_production', $store->mayar_api_key_production) }}">
                                </div>
                                <div>
                                    <label class="form-label-dark" style="font-size: 12px;">Webhook Token Production</label>
                                    <input type="password" name="mayar_webhook_token_production" class="form-control form-control-dark font-mono small" 
                                           placeholder="Token webhook production" value="{{ old('mayar_webhook_token_production', $store->mayar_webhook_token_production) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    @php
                        $webhookUrl = url('/api/v1/payment/mayar/webhook/' . $store->slug);
                        $activeToken = ($superCurEnv === 'production' && !empty($store->mayar_webhook_token_production)) 
                            ? $store->mayar_webhook_token_production 
                            : $store->mayar_webhook_token;
                        if (!empty($activeToken)) {
                            $webhookUrl .= '?token=' . urlencode($activeToken);
                        }
                    @endphp
                    <div class="p-2 rounded bg-black bg-opacity-25 border border-secondary font-mono small text-muted">
                        Webhook Callback: <code>{{ $webhookUrl }}</code>
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
