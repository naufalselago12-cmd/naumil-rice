@extends('store_backend.layouts.app')

@section('title', 'Pengaturan Pembayaran & Cabang - ' . $store->name)

@section('content')
<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-1 text-dark">Pengaturan Cabang: {{ $store->name }}</h2>
            <p class="text-muted mb-0">Atur profil operasional cabang serta pilih metode pembayaran wajib (QRIS Statik atau Payment Gateway Mayar.id).</p>
        </div>
        <span class="badge bg-primary px-3 py-2 font-mono fs-6">{{ $store->code }}</span>
    </div>
</div>

<div class="card-custom max-w-900 shadow-sm border-0">
    <div class="card-custom-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
        <h5 class="card-custom-title mb-0 fw-bold text-dark">
            <i class="fa-solid fa-sliders text-primary me-2"></i> Konfigurasi Cabang & Pembayaran
        </h5>
        <span class="badge {{ $store->isPaymentGateway() ? 'bg-success' : 'bg-info' }} text-white">
            <i class="fa-solid {{ $store->isPaymentGateway() ? 'fa-credit-card' : 'fa-qrcode' }} me-1"></i>
            {{ $store->isPaymentGateway() ? 'Payment Gateway Aktif' : 'QRIS Statik Aktif' }}
        </span>
    </div>

    <div class="p-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 mb-4 shadow-sm" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger border-0 mb-4 shadow-sm">
                <div class="fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-1"></i> Mohon perbaiki beberapa kesalahan berikut:</div>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('store.backend.settings.update', $store->slug) }}" method="POST" enctype="multipart/form-data" id="storeSettingsForm">
            @csrf
            @method('PUT')

            <!-- SECTION 1: PROFIL & KONTAK -->
            <div class="mb-4">
                <h6 class="fw-bold text-uppercase text-muted small letter-spacing-1 mb-3">
                    <i class="fa-solid fa-store me-1 text-primary"></i> 1. Informasi Outlet / Dapur
                </h6>

                <div class="mb-3">
                    <label class="form-label fw-bold">Alamat Lengkap Outlet / Dapur <span class="text-danger">*</span></label>
                    <textarea name="address" rows="2" class="form-control" required placeholder="Jalan, RT/RW, Kecamatan, Kota...">{{ old('address', $store->address) }}</textarea>
                    <small class="text-muted">Alamat dapur pengiriman pesanan untuk pelanggan.</small>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nomor WhatsApp Cabang</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-success"><i class="fa-brands fa-whatsapp"></i></span>
                            <input type="text" name="phone" class="form-control font-mono" 
                                   placeholder="Contoh: 081298765432" value="{{ old('phone', $store->phone) }}">
                        </div>
                        <small class="text-muted">Digunakan pelanggan untuk konfirmasi pesanan via WhatsApp.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Logo Toko (Upload File)</label>
                        <input type="file" name="logo_file" class="form-control" accept="image/*">
                        @if($store->logo)
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <img src="{{ $store->logo }}" alt="Logo" class="rounded border" style="width: 40px; height: 40px; object-fit: cover;">
                                <small class="text-muted text-truncate" style="max-width: 250px;">{{ $store->logo }}</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <hr class="my-4 text-muted">

            <!-- SECTION 2: METODE PEMBAYARAN WAJIB -->
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold text-uppercase text-muted small letter-spacing-1 mb-0">
                        <i class="fa-solid fa-wallet me-1 text-primary"></i> 2. Pilihan Wajib Metode Pembayaran Utama <span class="text-danger">*</span>
                    </h6>
                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-asterisk me-1"></i> Pilih Salah Satu</span>
                </div>
                <p class="text-muted small mb-3">
                    Toko wajib mengaktifkan salah satu metode pembayaran digital: <strong>QRIS Statik Toko</strong> atau <strong>Payment Gateway (Mayar.id)</strong>.
                </p>

                @php
                    $selectedPaymentType = old('payment_type', $store->payment_type ?? 'qris_static');
                @endphp

                <!-- Radio Choice Cards -->
                <div class="row g-3 mb-4">
                    <!-- Option A: QRIS Statik -->
                    <div class="col-md-6">
                        <label class="payment-type-card h-100 p-3 border rounded-3 d-flex flex-column cursor-pointer transition {{ $selectedPaymentType === 'qris_static' ? 'active-card border-primary bg-primary-subtle' : 'bg-white' }}" for="type_qris_static">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="radio" name="payment_type" id="type_qris_static" value="qris_static" 
                                           class="form-check-input" {{ $selectedPaymentType === 'qris_static' ? 'checked' : '' }}
                                           onchange="togglePaymentSection('qris_static')">
                                    <span class="fw-bold text-dark fs-6"><i class="fa-solid fa-qrcode text-primary me-1"></i> QRIS Statik Toko</span>
                                </div>
                                <span class="badge bg-primary">Manual / Statik</span>
                            </div>
                            <p class="text-muted small mb-0 flex-grow-1">
                                Pelanggan melakukan scan QRIS manual (upload gambar QRIS atau string merchant). Pembeli konfirmasi pembayaran manual ke kasir / WhatsApp toko.
                            </p>
                        </label>
                    </div>

                    <!-- Option B: Payment Gateway Mayar.id -->
                    <div class="col-md-6">
                        <label class="payment-type-card h-100 p-3 border rounded-3 d-flex flex-column cursor-pointer transition {{ $selectedPaymentType === 'payment_gateway' ? 'active-card border-success bg-success-subtle' : 'bg-white' }}" for="type_payment_gateway">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="radio" name="payment_type" id="type_payment_gateway" value="payment_gateway" 
                                           class="form-check-input" {{ $selectedPaymentType === 'payment_gateway' ? 'checked' : '' }}
                                           onchange="togglePaymentSection('payment_gateway')">
                                    <span class="fw-bold text-dark fs-6"><i class="fa-solid fa-bolt text-warning me-1"></i> Payment Gateway (Mayar.id)</span>
                                </div>
                                <span class="badge bg-success">Otomatis / Real-Time</span>
                            </div>
                            <p class="text-muted small mb-0 flex-grow-1">
                                Pembayaran online terintegrasi Mayar.id (QRIS dinamis otomatis, Virtual Account BCA/Mandiri/BRI, E-Wallet). Pesanan otomatis berubah jadi <strong>Lunas</strong> via Webhook!
                            </p>
                        </label>
                    </div>
                </div>

                <!-- SUB-SECTION A: QRIS STATIK FORM -->
                <div id="section_qris_static" class="p-3 rounded-3 bg-light border mb-4 {{ $selectedPaymentType === 'qris_static' ? '' : 'd-none' }}">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="fa-solid fa-qrcode text-primary fs-5"></i>
                        <h6 class="fw-bold mb-0 text-dark">Pengaturan QRIS Statik / Dinamis Mandiri</h6>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Upload File Gambar QRIS</label>
                            <input type="file" name="qris_file" class="form-control mb-2" accept="image/*">
                            @if($store->qris_image)
                                <div class="d-flex align-items-center gap-3 p-2 bg-white rounded border mt-2">
                                    <img src="{{ $store->qris_image }}" alt="QRIS" class="border rounded" style="width: 70px; height: 70px; object-fit: contain;">
                                    <div>
                                        <div class="text-success fw-bold small"><i class="fa-solid fa-circle-check"></i> File QRIS Tersimpan</div>
                                        <small class="text-muted d-block font-mono text-truncate" style="max-width: 200px;">{{ $store->qris_image }}</small>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">QRIS String Payload (Data Merchant Resmi)</label>
                            <textarea name="qris_data" rows="3" class="form-control font-mono small" 
                                      placeholder="00020101021226600014ID.LINKAJA.WWW0118936009180000...">{{ old('qris_data', $store->qris_data) }}</textarea>
                            <small class="text-muted">Jika diisi, sistem dapat membuat kode QR dinamis otomatis dengan menambahkan nominal pesanan.</small>
                        </div>
                    </div>
                </div>

                <!-- SUB-SECTION B: PAYMENT GATEWAY MAYAR.ID FORM -->
                <div id="section_payment_gateway" class="p-3 rounded-3 bg-light border mb-4 {{ $selectedPaymentType === 'payment_gateway' ? '' : 'd-none' }}">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-credit-card text-success fs-5"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Konfigurasi Mayar.id Payment Gateway</h6>
                                <small class="text-muted">Koneksikan akun Mayar.id untuk menerima pembayaran online otomatis.</small>
                            </div>
                        </div>
                        <span class="badge {{ $store->hasMayarConfigured() ? 'bg-success' : 'bg-secondary' }}">
                            <i class="fa-solid {{ $store->hasMayarConfigured() ? 'fa-link' : 'fa-link-slash' }} me-1"></i>
                            {{ $store->hasMayarConfigured() ? 'Kredensial Terisi' : 'Belum Dikonfigurasi' }}
                        </span>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Lingkungan / Environment <span class="text-danger">*</span></label>
                            <select name="mayar_environment" id="mayarEnvironment" class="form-select">
                                <option value="sandbox" {{ old('mayar_environment', $store->mayar_environment ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>
                                    Sandbox (Testing / Uji Coba)
                                </option>
                                <option value="production" {{ old('mayar_environment', $store->mayar_environment) === 'production' ? 'selected' : '' }}>
                                    Production (Live Transaksi Nyata)
                                </option>
                            </select>
                            <small class="text-muted">Pilih Sandbox saat tes, Production saat siap jualan nyata.</small>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label small fw-bold">API Key Mayar.id (Bearer Token) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fa-solid fa-key text-muted"></i></span>
                                <input type="password" name="mayar_api_key" id="mayarApiKey" class="form-control font-mono" 
                                       placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6..." 
                                       value="{{ old('mayar_api_key', $store->mayar_api_key) }}">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('mayarApiKey', this)">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Dapatkan dari menu <em>Integration / API Keys</em> di dashboard Mayar.id Anda.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Webhook Token / Secret (Opsional)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-shield-halved text-muted"></i></span>
                            <input type="password" name="mayar_webhook_token" id="mayarWebhookToken" class="form-control font-mono" 
                                   placeholder="Token rahasia webhook Mayar..." 
                                   value="{{ old('mayar_webhook_token', $store->mayar_webhook_token) }}">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('mayarWebhookToken', this)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Untuk validasi tanda tangan callback webhook dari Mayar.id.</small>
                    </div>

                    <!-- Webhook Callback URL Copyable Box -->
                    @php
                        $webhookUrl = url('/api/v1/payment/mayar/webhook/' . $store->slug);
                    @endphp
                    <div class="p-3 bg-white border rounded mb-3">
                        <label class="form-label small fw-bold text-dark mb-1 d-flex align-items-center justify-content-between">
                            <span><i class="fa-solid fa-bullhorn text-primary me-1"></i> Webhook Callback URL Toko Ini (Masukkan di Mayar.id):</span>
                            <span class="badge bg-light text-dark font-mono border">POST Callback</span>
                        </label>
                        <div class="input-group">
                            <input type="text" id="webhookUrlInput" class="form-control font-mono small bg-light" readonly value="{{ $webhookUrl }}">
                            <button type="button" class="btn btn-outline-primary" onclick="copyWebhookUrl()">
                                <i class="fa-solid fa-copy me-1"></i> Salin URL
                            </button>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Salin URL di atas dan tempelkan ke <strong>Settings &gt; Webhook URL</strong> di dashboard Mayar.id Anda agar pembayaran pelanggan otomatis terverifikasi.
                        </small>
                    </div>

                    <!-- Test Connection Button & Alert -->
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-dark btn-sm" id="btnTestMayar" onclick="testMayarConnection()">
                            <i class="fa-solid fa-plug me-1"></i> Uji Koneksi API Mayar.id
                        </button>
                        <span id="testMayarResult" class="small"></span>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end pt-3 border-top">
                <button type="submit" class="btn btn-primary px-4 py-2 fw-bold shadow-sm">
                    <i class="fa-solid fa-save me-1"></i> Simpan Pengaturan Cabang
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePaymentSection(type) {
    const sectionQris = document.getElementById('section_qris_static');
    const sectionGateway = document.getElementById('section_payment_gateway');
    const cardQris = document.querySelector('label[for="type_qris_static"]');
    const cardGateway = document.querySelector('label[for="type_payment_gateway"]');

    if (type === 'qris_static') {
        sectionQris.classList.remove('d-none');
        sectionGateway.classList.add('d-none');

        cardQris.classList.add('active-card', 'border-primary', 'bg-primary-subtle');
        cardQris.classList.remove('bg-white');

        cardGateway.classList.remove('active-card', 'border-success', 'bg-success-subtle');
        cardGateway.classList.add('bg-white');
    } else {
        sectionGateway.classList.remove('d-none');
        sectionQris.classList.add('d-none');

        cardGateway.classList.add('active-card', 'border-success', 'bg-success-subtle');
        cardGateway.classList.remove('bg-white');

        cardQris.classList.remove('active-card', 'border-primary', 'bg-primary-subtle');
        cardQris.classList.add('bg-white');
    }
}

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function copyWebhookUrl() {
    const input = document.getElementById('webhookUrlInput');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        alert('Webhook URL berhasil disalin ke clipboard!');
    }).catch(() => {
        document.execCommand('copy');
        alert('Webhook URL berhasil disalin!');
    });
}

async function testMayarConnection() {
    const apiKey = document.getElementById('mayarApiKey').value.trim();
    const env = document.getElementById('mayarEnvironment').value;
    const btn = document.getElementById('btnTestMayar');
    const resultSpan = document.getElementById('testMayarResult');

    if (!apiKey) {
        resultSpan.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-circle-exclamation me-1"></i> Masukkan API Key terlebih dahulu.</span>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Menghubungi Mayar.id...';
    resultSpan.innerHTML = '';

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch("{{ route('store.backend.settings.testMayar', $store->slug) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                api_key: apiKey,
                environment: env
            })
        });

        const data = await res.json();
        if (data.success) {
            resultSpan.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> ' + (data.message || 'Koneksi ke Mayar.id Berhasil!') + '</span>';
        } else {
            resultSpan.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> ' + (data.message || 'Koneksi Gagal') + '</span>';
        }
    } catch (err) {
        console.error('Test Mayar error', err);
        resultSpan.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> Gagal menghubungi server: ' + err.message + '</span>';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-plug me-1"></i> Uji Koneksi API Mayar.id';
    }
}
</script>

<style>
.cursor-pointer {
    cursor: pointer;
}
.transition {
    transition: all 0.2s ease-in-out;
}
.payment-type-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    border-color: #0284c7 !important;
}
.letter-spacing-1 {
    letter-spacing: 0.5px;
}
</style>
@endsection
