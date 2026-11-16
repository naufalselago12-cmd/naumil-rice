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

                    <!-- Option B: Payment Gateway -->
                    <div class="col-md-6">
                        <label class="payment-type-card h-100 p-3 border rounded-3 d-flex flex-column cursor-pointer transition {{ $selectedPaymentType === 'payment_gateway' ? 'active-card border-success bg-success-subtle' : 'bg-white' }}" for="type_payment_gateway">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="radio" name="payment_type" id="type_payment_gateway" value="payment_gateway" 
                                           class="form-check-input" {{ $selectedPaymentType === 'payment_gateway' ? 'checked' : '' }}
                                           onchange="togglePaymentSection('payment_gateway')">
                                    <span class="fw-bold text-dark fs-6"><i class="fa-solid fa-bolt text-warning me-1"></i> Payment Gateway Otomatis</span>
                                </div>
                                <span class="badge bg-success">Otomatis / Real-Time</span>
                            </div>
                            <p class="text-muted small mb-0 flex-grow-1">
                                Pembayaran online terintegrasi (Midtrans atau Mayar.id): QRIS otomatis, Virtual Account bank, E-Wallet (GoPay, ShopeePay, Dana) & Kartu Kredit. Pesanan otomatis berubah jadi <strong>Lunas</strong> via Webhook!
                            </p>
                        </label>
                    </div>
                </div>

                <!-- SUB-SECTION A: QRIS STATIK FORM -->
                <div id="section_qris_static" class="p-3 rounded-3 bg-light border mb-4 {{ $selectedPaymentType === 'qris_static' ? '' : 'd-none' }}">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fa-solid fa-qrcode text-primary fs-5"></i>
                        <h6 class="fw-bold mb-0 text-dark">Upload Gambar QRIS Statik Toko</h6>
                        <span class="badge bg-primary ms-auto">QRIS Statik</span>
                    </div>
                    <p class="text-muted small mb-3">
                        Cukup upload satu file gambar QRIS resmi toko Anda (format JPG, PNG, atau WEBP). Gambar ini yang akan ditampilkan langsung ke pelanggan untuk di-scan dan diunduh saat melakukan pembayaran order.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-bold">Pilih File Gambar QRIS Statik Toko <span class="text-danger">*</span></label>
                            <input type="file" name="qris_file" class="form-control mb-2" accept="image/*">
                            <small class="text-muted d-block"><i class="fa-solid fa-circle-info me-1"></i> Disarankan gambar persegi (1:1) berkualitas tajam dan jelas agar mudah di-scan oleh pelanggan.</small>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Pratinjau QRIS Aktif</label>
                            @if($store->qris_image)
                                <div class="p-3 bg-white rounded border text-center shadow-sm">
                                    <img src="{{ $store->qris_image }}" alt="QRIS Statik Toko" class="img-fluid rounded border mb-2 shadow-sm" style="max-height: 140px; object-fit: contain;">
                                    <div class="text-success fw-bold small"><i class="fa-solid fa-circle-check"></i> File QRIS Tersimpan & Aktif</div>
                                    <a href="{{ $store->qris_image }}" target="_blank" class="btn btn-outline-primary btn-sm mt-2">
                                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Lihat Gambar Penuh
                                    </a>
                                </div>
                            @else
                                <div class="p-3 bg-white rounded border text-center text-muted">
                                    <i class="fa-solid fa-image fa-2x mb-2 text-secondary"></i>
                                    <small class="d-block">Belum ada file QRIS yang di-upload.</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- SUB-SECTION B: PAYMENT GATEWAY FORM (MIDTRANS & MAYAR) -->
                <div id="section_payment_gateway" class="p-3 rounded-3 bg-light border mb-4 {{ $selectedPaymentType === 'payment_gateway' ? '' : 'd-none' }}">
                    @php
                        $selectedProvider = old('payment_gateway_provider', $store->payment_gateway_provider ?? 'midtrans');
                    @endphp

                    <!-- 1. PILIH PROVIDER PAYMENT GATEWAY -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark d-flex align-items-center gap-2 mb-2">
                            <i class="fa-solid fa-server text-primary"></i> Pilih Layanan Payment Gateway:
                        </label>
                        <div class="row g-2">
                            <!-- Provider A: Midtrans -->
                            <div class="col-md-6">
                                <label class="provider-choice-card border rounded-3 p-3 d-flex align-items-start gap-3 cursor-pointer transition w-100 {{ $selectedProvider === 'midtrans' ? 'border-primary bg-primary bg-opacity-10 shadow-sm' : 'border-light bg-white' }}" id="card_provider_midtrans" for="provider_midtrans">
                                    <input type="radio" name="payment_gateway_provider" id="provider_midtrans" value="midtrans"
                                           class="form-check-input mt-1" {{ $selectedProvider === 'midtrans' ? 'checked' : '' }}
                                           onchange="toggleGatewayProvider('midtrans')">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="fw-bold text-dark"><i class="fa-solid fa-credit-card text-primary me-1"></i> Midtrans Payment Gateway</span>
                                            <span class="badge bg-primary">Rekomendasi</span>
                                        </div>
                                        <small class="text-muted d-block">
                                            Snap all-in-one: QRIS (GoPay, ShopeePay, Dana, OVO), Virtual Account (BCA, Mandiri, BRI, BNI, Permata), Kartu Kredit & Debit.
                                        </small>
                                    </div>
                                </label>
                            </div>

                            <!-- Provider B: Mayar.id -->
                            <div class="col-md-6">
                                <label class="provider-choice-card border rounded-3 p-3 d-flex align-items-start gap-3 cursor-pointer transition w-100 {{ $selectedProvider === 'mayar' ? 'border-success bg-success bg-opacity-10 shadow-sm' : 'border-light bg-white' }}" id="card_provider_mayar" for="provider_mayar">
                                    <input type="radio" name="payment_gateway_provider" id="provider_mayar" value="mayar"
                                           class="form-check-input mt-1" {{ $selectedProvider === 'mayar' ? 'checked' : '' }}
                                           onchange="toggleGatewayProvider('mayar')">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="fw-bold text-dark"><i class="fa-solid fa-bolt text-warning me-1"></i> Mayar.id Gateway</span>
                                            <span class="badge bg-secondary">Alternatif</span>
                                        </div>
                                        <small class="text-muted d-block">
                                            Pembayaran via Payment Link invoice Mayar.id dengan auto-redirect dan sinkronisasi status pesanan.
                                        </small>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- PANEL PROVIDER 1: MIDTRANS                     -->
                    <!-- ============================================== -->
                    <div id="box_provider_midtrans" class="{{ $selectedProvider === 'midtrans' ? '' : 'd-none' }}">
                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2 pt-2 border-top">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-credit-card text-primary fs-5"></i>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Konfigurasi Midtrans Payment Gateway (Snap API)</h6>
                                    <small class="text-muted">Hubungkan akun Midtrans cabang Anda untuk menerima pembayaran otomatis.</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge {{ $store->hasMidtransSandboxConfigured() ? 'bg-warning text-dark' : 'bg-secondary' }}">
                                    <i class="fa-solid fa-flask me-1"></i> Sandbox: {{ $store->hasMidtransSandboxConfigured() ? 'Terisi' : 'Belum' }}
                                </span>
                                <span class="badge {{ $store->hasMidtransProductionConfigured() ? 'bg-success' : 'bg-secondary' }}">
                                    <i class="fa-solid fa-circle-check me-1"></i> Production: {{ $store->hasMidtransProductionConfigured() ? 'Terisi' : 'Belum' }}
                                </span>
                            </div>
                        </div>

                        @php
                            $midEnv = old('midtrans_environment', $store->midtrans_environment ?? 'sandbox');
                        @endphp

                        <!-- 1. MODE ENVIRONMENT MIDTRANS (SANDBOX / PRODUCTION) -->
                        <div class="p-3 bg-white border rounded-3 mb-3 shadow-sm">
                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                <label class="form-label small fw-bold text-dark mb-0 d-flex align-items-center gap-1">
                                    <i class="fa-solid fa-tower-broadcast text-primary"></i>
                                    Mode Lingkungan Midtrans (Environment Aktif) <span class="text-danger">*</span>
                                </label>
                                <span class="badge {{ $midEnv === 'production' ? 'bg-success' : 'bg-warning text-dark' }}" id="midtransEnvBadge">
                                    Mode Aktif: {{ strtoupper($midEnv) }}
                                </span>
                            </div>
                            <p class="small text-muted mb-3">
                                Pilih lingkungan yang aktif. Gunakan <strong>Sandbox</strong> untuk development & pengujian (bebas biaya tanpa uang asli via Simulator Midtrans). Pindahkan ke <strong>Production</strong> saat cabang siap menerima pembayaran live dari pelanggan.
                            </p>

                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="border rounded-3 p-3 d-flex align-items-start gap-3 cursor-pointer transition w-100 {{ $midEnv === 'sandbox' ? 'border-warning bg-warning bg-opacity-10 shadow-sm' : 'border-light bg-light' }}" id="card_mid_env_sandbox" for="radio_mid_sandbox">
                                        <input type="radio" name="midtrans_environment" id="radio_mid_sandbox" value="sandbox" 
                                               class="form-check-input mt-1" {{ $midEnv === 'sandbox' ? 'checked' : '' }}
                                               onchange="handleMidtransEnvChange('sandbox')">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="fw-bold text-dark"><i class="fa-solid fa-flask text-warning me-1"></i> Sandbox (Testing / Uji Coba)</span>
                                                <span class="badge bg-warning text-dark small">Simulasi</span>
                                            </div>
                                            <small class="text-muted d-block mt-1">Menggunakan API Sandbox Midtrans (app.sandbox.midtrans.com). Transaksi simulasi tanpa uang riil.</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <label class="border rounded-3 p-3 d-flex align-items-start gap-3 cursor-pointer transition w-100 {{ $midEnv === 'production' ? 'border-success bg-success bg-opacity-10 shadow-sm' : 'border-light bg-light' }}" id="card_mid_env_production" for="radio_mid_production">
                                        <input type="radio" name="midtrans_environment" id="radio_mid_production" value="production" 
                                               class="form-check-input mt-1" {{ $midEnv === 'production' ? 'checked' : '' }}
                                               onchange="handleMidtransEnvChange('production')">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="fw-bold text-dark"><i class="fa-solid fa-circle-check text-success me-1"></i> Production (Live Transaksi)</span>
                                                <span class="badge bg-success small">Live</span>
                                            </div>
                                            <small class="text-muted d-block mt-1">Menggunakan API Production Midtrans (app.midtrans.com). Memproses pembayaran nyata dari pembeli.</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- 2. KREDENSIAL MIDTRANS SANDBOX & PRODUCTION -->
                        <div class="row g-3 mb-3">
                            <!-- BOX KREDENSIAL SANDBOX -->
                            <div class="col-md-6">
                                <div class="card h-100 border transition {{ $midEnv === 'sandbox' ? 'border-warning shadow-sm' : '' }}" id="box_mid_sandbox_fields">
                                    <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-flask text-warning"></i>
                                            <strong class="small text-dark">Kredensial Midtrans Sandbox</strong>
                                        </div>
                                        <span class="badge {{ $midEnv === 'sandbox' ? 'bg-warning text-dark' : 'bg-light text-muted border' }}" id="tag_mid_sandbox_active">
                                            {{ $midEnv === 'sandbox' ? 'Aktif Dipakai' : 'Standby' }}
                                        </span>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Server Key Sandbox <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="fa-solid fa-key text-muted"></i></span>
                                                <input type="password" name="midtrans_server_key" id="midtransServerKey" class="form-control font-mono small" 
                                                       placeholder="SB-Mid-server-..." 
                                                       value="{{ old('midtrans_server_key', $store->midtrans_server_key) }}">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('midtransServerKey', this)">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Dari dashboard Midtrans Sandbox: <em>Settings -> Access Keys</em></small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Client Key Sandbox <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="fa-solid fa-shield text-muted"></i></span>
                                                <input type="password" name="midtrans_client_key" id="midtransClientKey" class="form-control font-mono small" 
                                                       placeholder="SB-Mid-client-..." 
                                                       value="{{ old('midtrans_client_key', $store->midtrans_client_key) }}">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('midtransClientKey', this)">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Digunakan untuk memuat UI Snap pembayaran di sisi browser.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Merchant ID Sandbox (Opsional)</label>
                                            <input type="text" name="midtrans_merchant_id" class="form-control font-mono small" 
                                                   placeholder="G123456789" 
                                                   value="{{ old('midtrans_merchant_id', $store->midtrans_merchant_id) }}">
                                        </div>

                                        <div class="d-flex align-items-center gap-2 pt-2 border-top flex-wrap">
                                            <button type="button" class="btn btn-outline-warning btn-sm text-dark fw-bold" onclick="testMidtransSpecific('sandbox')">
                                                <i class="fa-solid fa-plug me-1"></i> Uji Koneksi Sandbox
                                            </button>
                                            <span id="testMidSandboxResult" class="small"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- BOX KREDENSIAL PRODUCTION -->
                            <div class="col-md-6">
                                <div class="card h-100 border transition {{ $midEnv === 'production' ? 'border-success shadow-sm' : '' }}" id="box_mid_production_fields">
                                    <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-circle-check text-success"></i>
                                            <strong class="small text-dark">Kredensial Midtrans Production (Live)</strong>
                                        </div>
                                        <span class="badge {{ $midEnv === 'production' ? 'bg-success' : 'bg-light text-muted border' }}" id="tag_mid_production_active">
                                            {{ $midEnv === 'production' ? 'Aktif Dipakai' : 'Standby' }}
                                        </span>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Server Key Production <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="fa-solid fa-key text-muted"></i></span>
                                                <input type="password" name="midtrans_server_key_production" id="midtransServerKeyProduction" class="form-control font-mono small" 
                                                       placeholder="Mid-server-..." 
                                                       value="{{ old('midtrans_server_key_production', $store->midtrans_server_key_production) }}">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('midtransServerKeyProduction', this)">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Dari dashboard Midtrans Production: <em>Settings -> Access Keys</em></small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Client Key Production <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="fa-solid fa-shield text-muted"></i></span>
                                                <input type="password" name="midtrans_client_key_production" id="midtransClientKeyProduction" class="form-control font-mono small" 
                                                       placeholder="Mid-client-..." 
                                                       value="{{ old('midtrans_client_key_production', $store->midtrans_client_key_production) }}">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('midtransClientKeyProduction', this)">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Digunakan browser live pembeli untuk transaksi sesungguhnya.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Merchant ID Production (Opsional)</label>
                                            <input type="text" name="midtrans_merchant_id_production" class="form-control font-mono small" 
                                                   placeholder="G123456789" 
                                                   value="{{ old('midtrans_merchant_id_production', $store->midtrans_merchant_id_production) }}">
                                        </div>

                                        <div class="d-flex align-items-center gap-2 pt-2 border-top flex-wrap">
                                            <button type="button" class="btn btn-outline-success btn-sm fw-bold" onclick="testMidtransSpecific('production')">
                                                <i class="fa-solid fa-plug me-1"></i> Uji Koneksi Production
                                            </button>
                                            <span id="testMidProductionResult" class="small"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. NOTIFICATION / WEBHOOK URL MIDTRANS -->
                        @php
                            $midtransWebhookUrl = url('/api/v1/payment/midtrans/notification/' . $store->slug);
                        @endphp
                        <div class="p-3 bg-white border rounded mb-3 shadow-sm">
                            <label class="form-label small fw-bold text-dark mb-1 d-flex align-items-center justify-content-between">
                                <span><i class="fa-solid fa-bullhorn text-primary me-1"></i> Payment Notification URL (Masukkan di Dashboard Midtrans MAP):</span>
                                <span class="badge bg-light text-dark font-mono border">POST Webhook</span>
                            </label>
                            <div class="input-group mb-2">
                                <input type="text" id="midtransWebhookUrlInput" class="form-control font-mono small bg-light" readonly value="{{ $midtransWebhookUrl }}">
                                <button type="button" class="btn btn-outline-primary" onclick="copyMidtransWebhookUrl()">
                                    <i class="fa-solid fa-copy me-1"></i> Salin URL
                                </button>
                                <a href="{{ $midtransWebhookUrl }}" target="_blank" class="btn btn-outline-secondary" title="Cek apakah endpoint aktif">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Cek Endpoint
                                </a>
                            </div>
                            <div class="p-2 rounded bg-light border small text-muted">
                                <i class="fa-solid fa-circle-check text-success me-1"></i>
                                <strong>Panduan Integrasi Webhook:</strong> Buka <strong>Midtrans Dashboard MAP</strong> &rarr; menu <strong>Settings</strong> &rarr; <strong>Configuration</strong> &rarr; tempelkan URL di atas pada kolom <em>Payment Notification URL</em> &rarr; klik <strong>Save Changes</strong>.
                                Sistem otomatis memvalidasi <em>SHA512 Signature Key</em>, mengubah status pesanan menjadi <strong>LUNAS (Paid)</strong> seketika saat pelanggan menyelesaikan pembayaran di Midtrans!
                            </div>
                        </div>

                        <!-- 4. UJI COBA & SIMULASI TRANSAKSI MIDTRANS -->
                        <div class="card border-primary border-opacity-50 mb-3 shadow-sm bg-white">
                            <div class="card-header bg-primary bg-opacity-10 py-2 d-flex align-items-center justify-content-between">
                                <strong class="text-primary small">
                                    <i class="fa-solid fa-flask-vial me-1"></i> Uji Coba & Simulasi Transaksi Midtrans Snap
                                </strong>
                                <span class="badge bg-primary">Uji Coba Langsung</span>
                            </div>
                            <div class="card-body p-3">
                                <p class="text-muted small mb-3">
                                    Gunakan fitur ini untuk membuat transaksi Snap uji coba langsung dengan kredensial yang tersimpan. Anda dapat membuka pop-up pembayaran Midtrans dan menggunakan Simulator resmi Midtrans untuk mencoba simulasi scan QRIS atau bayar Virtual Account.
                                </p>

                                <div class="row g-2 align-items-center mb-3">
                                    <div class="col-auto">
                                        <label class="small fw-bold text-dark">Nominal Simulasi:</label>
                                    </div>
                                    <div class="col-auto" style="width: 170px;">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" id="simAmount" class="form-control font-mono" value="10000" min="1000" step="1000">
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-primary btn-sm fw-bold px-3" id="btnStartSim" onclick="startMidtransSimulation()">
                                            <i class="fa-solid fa-play me-1"></i> Buat Transaksi Uji Coba
                                        </button>
                                    </div>
                                    <div class="col-auto">
                                        <a href="https://simulator.sandbox.midtrans.com/" target="_blank" class="btn btn-outline-secondary btn-sm">
                                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Buka Midtrans Simulator
                                        </a>
                                    </div>
                                </div>

                                <!-- Simulation Result Box -->
                                <div id="simResultBox" class="p-3 rounded bg-light border d-none">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <strong class="text-success small">
                                            <i class="fa-solid fa-circle-check me-1"></i> Transaksi Uji Coba Berhasil Dibuat!
                                        </strong>
                                        <span id="simStatusBadge" class="badge bg-warning text-dark">Status: PENDING</span>
                                    </div>

                                    <div class="row g-2 small mb-3">
                                        <div class="col-sm-6">
                                            <span class="text-muted d-block">Order ID Simulasi:</span>
                                            <code class="fw-bold fs-6 text-dark" id="simOrderId">-</code>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted d-block">Snap Token:</span>
                                            <code class="small text-truncate d-block" id="simSnapToken">-</code>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                                        <button type="button" class="btn btn-success btn-sm fw-bold" id="btnOpenSimPayment" onclick="openSimPayment()">
                                            <i class="fa-solid fa-credit-card me-1"></i> Buka Pembayaran Snap (Pop-up)
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm fw-bold" onclick="checkSimulationStatus()">
                                            <i class="fa-solid fa-rotate me-1"></i> Cek Status Pembayaran Uji Coba
                                        </button>
                                        <span id="simCheckMessage" class="small text-muted"></span>
                                    </div>

                                    <div class="p-2 rounded bg-white border small text-muted">
                                        <strong><i class="fa-solid fa-lightbulb text-warning me-1"></i> Cara Mengetes Pembayaran di Midtrans Simulator:</strong>
                                        <ol class="mb-0 ps-3 mt-1">
                                            <li>Klik tombol hijau <strong>"Buka Pembayaran Snap"</strong> di atas.</li>
                                            <li>Pilih metode pembayaran (misalnya: <strong>BCA Virtual Account</strong> atau <strong>QRIS</strong>).</li>
                                            <li>Salin nomor Virtual Account yang muncul (atau download gambar QRIS).</li>
                                            <li>Buka <a href="https://simulator.sandbox.midtrans.com/" target="_blank" class="fw-bold">Midtrans Simulator</a>, pilih menu yang sesuai (misal: BCA VA Simulator), masukkan nomor VA dan klik <strong>Inquire &rarr; Pay</strong>.</li>
                                            <li>Kembali ke halaman ini dan klik tombol <strong>"Cek Status Pembayaran Uji Coba"</strong> &mdash; status akan otomatis berubah menjadi <span class="badge bg-success">LUNAS (Settlement)</span>!</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- PANEL PROVIDER 2: MAYAR.ID                     -->
                    <!-- ============================================== -->
                    <div id="box_provider_mayar" class="{{ $selectedProvider === 'mayar' ? '' : 'd-none' }}">
                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2 pt-2 border-top">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-bolt text-warning fs-5"></i>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Konfigurasi Mayar.id Payment Gateway</h6>
                                    <small class="text-muted">Koneksikan akun Mayar.id untuk menerima pembayaran online via invoice link.</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge {{ $store->hasSandboxConfigured() ? 'bg-warning text-dark' : 'bg-secondary' }}">
                                    <i class="fa-solid fa-flask me-1"></i> Sandbox: {{ $store->hasSandboxConfigured() ? 'Terisi' : 'Belum' }}
                                </span>
                                <span class="badge {{ $store->hasProductionConfigured() ? 'bg-success' : 'bg-secondary' }}">
                                    <i class="fa-solid fa-circle-check me-1"></i> Production: {{ $store->hasProductionConfigured() ? 'Terisi' : 'Belum' }}
                                </span>
                            </div>
                        </div>

                        @php
                            $curEnv = old('mayar_environment', $store->mayar_environment ?? 'sandbox');
                        @endphp

                        <!-- 1. PILIHAN ENVIRONMENT VIA RADIO BUTTON -->
                        <div class="p-3 bg-white border rounded-3 mb-3 shadow-sm">
                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                <label class="form-label small fw-bold text-dark mb-0 d-flex align-items-center gap-1">
                                    <i class="fa-solid fa-tower-broadcast text-primary"></i>
                                    Mode Lingkungan Mayar (Environment Aktif) <span class="text-danger">*</span>
                                </label>
                                <span class="badge {{ $curEnv === 'production' ? 'bg-success' : 'bg-warning text-dark' }}" id="envStatusBadge">
                                    Mode Aktif: {{ strtoupper($curEnv) }}
                                </span>
                            </div>
                            <p class="small text-muted mb-3">
                                Pilih mode yang digunakan saat ini. Anda dapat berpindah ke mode <strong>Sandbox</strong> kapan saja saat ingin melakukan development/testing pembayaran tanpa perlu repot mengganti atau menghapus API Key Production.
                            </p>
                            
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="border rounded-3 p-3 d-flex align-items-start gap-3 cursor-pointer transition w-100 env-radio-card {{ $curEnv === 'sandbox' ? 'border-warning bg-warning bg-opacity-10 shadow-sm' : 'border-light bg-light' }}" id="card_env_sandbox" for="radio_env_sandbox">
                                        <input type="radio" name="mayar_environment" id="radio_env_sandbox" value="sandbox" 
                                               class="form-check-input mt-1" {{ $curEnv === 'sandbox' ? 'checked' : '' }}
                                               onchange="handleEnvChange('sandbox')">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="fw-bold text-dark"><i class="fa-solid fa-flask text-warning me-1"></i> Sandbox (Uji Coba / Dev)</span>
                                                <span class="badge bg-warning text-dark small">Testing</span>
                                            </div>
                                            <small class="text-muted d-block mt-1">Menggunakan API Sandbox (api.mayar.io). Transaksi simulasi tanpa uang nyata.</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <label class="border rounded-3 p-3 d-flex align-items-start gap-3 cursor-pointer transition w-100 env-radio-card {{ $curEnv === 'production' ? 'border-success bg-success bg-opacity-10 shadow-sm' : 'border-light bg-light' }}" id="card_env_production" for="radio_env_production">
                                        <input type="radio" name="mayar_environment" id="radio_env_production" value="production" 
                                               class="form-check-input mt-1" {{ $curEnv === 'production' ? 'checked' : '' }}
                                               onchange="handleEnvChange('production')">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="fw-bold text-dark"><i class="fa-solid fa-circle-check text-success me-1"></i> Production (Live)</span>
                                                <span class="badge bg-success small">Live Transaksi</span>
                                            </div>
                                            <small class="text-muted d-block mt-1">Menggunakan API Production (api.mayar.id). Memproses pembayaran riil dari pelanggan.</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- 2. KREDENSIAL SANDBOX & PRODUCTION (Keduanya Disediakan & Tersimpan) -->
                        <div class="row g-3 mb-3">
                            <!-- BOX KREDENSIAL SANDBOX -->
                            <div class="col-md-6">
                                <div class="card h-100 border transition {{ $curEnv === 'sandbox' ? 'border-warning shadow-sm' : '' }}" id="box_sandbox_fields">
                                    <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-flask text-warning"></i>
                                            <strong class="small text-dark">Kredensial Sandbox (Testing)</strong>
                                        </div>
                                        <span class="badge {{ $curEnv === 'sandbox' ? 'bg-warning text-dark' : 'bg-light text-muted border' }}" id="tag_sandbox_active">
                                            {{ $curEnv === 'sandbox' ? 'Aktif Dipakai' : 'Standby' }}
                                        </span>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">API Key Sandbox <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="fa-solid fa-key text-muted"></i></span>
                                                <input type="password" name="mayar_api_key" id="mayarApiKey" class="form-control font-mono small" 
                                                       placeholder="API Key Sandbox (eyJhbGci...)" 
                                                       value="{{ old('mayar_api_key', $store->mayar_api_key) }}">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('mayarApiKey', this)">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Dari dashboard Sandbox Mayar: <em>Integration / API Keys</em></small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Webhook Token Sandbox (Opsional)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="fa-solid fa-shield-halved text-muted"></i></span>
                                                <input type="password" name="mayar_webhook_token" id="mayarWebhookToken" class="form-control font-mono small" 
                                                       placeholder="Token rahasia webhook sandbox..." 
                                                       value="{{ old('mayar_webhook_token', $store->mayar_webhook_token) }}">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('mayarWebhookToken', this)">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Untuk validasi token callback di lingkungan Sandbox.</small>
                                        </div>

                                        <div class="d-flex align-items-center gap-2 pt-2 border-top flex-wrap">
                                            <button type="button" class="btn btn-outline-warning btn-sm text-dark" onclick="testMayarSpecific('sandbox')">
                                                <i class="fa-solid fa-plug me-1"></i> Uji Koneksi Sandbox
                                            </button>
                                            <span id="testSandboxResult" class="small"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- BOX KREDENSIAL PRODUCTION -->
                            <div class="col-md-6">
                                <div class="card h-100 border transition {{ $curEnv === 'production' ? 'border-success shadow-sm' : '' }}" id="box_production_fields">
                                    <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-circle-check text-success"></i>
                                            <strong class="small text-dark">Kredensial Production (Live)</strong>
                                        </div>
                                        <span class="badge {{ $curEnv === 'production' ? 'bg-success' : 'bg-light text-muted border' }}" id="tag_production_active">
                                            {{ $curEnv === 'production' ? 'Aktif Dipakai' : 'Standby' }}
                                        </span>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">API Key Production <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="fa-solid fa-key text-muted"></i></span>
                                                <input type="password" name="mayar_api_key_production" id="mayarApiKeyProduction" class="form-control font-mono small" 
                                                       placeholder="API Key Production (eyJhbGci...)" 
                                                       value="{{ old('mayar_api_key_production', $store->mayar_api_key_production) }}">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('mayarApiKeyProduction', this)">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Dari dashboard Production Mayar: <em>Integration / API Keys</em></small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Webhook Token Production (Opsional)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="fa-solid fa-shield-halved text-muted"></i></span>
                                                <input type="password" name="mayar_webhook_token_production" id="mayarWebhookTokenProduction" class="form-control font-mono small" 
                                                       placeholder="Token rahasia webhook production..." 
                                                       value="{{ old('mayar_webhook_token_production', $store->mayar_webhook_token_production) }}">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('mayarWebhookTokenProduction', this)">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Untuk validasi token callback di lingkungan Production.</small>
                                        </div>

                                        <div class="d-flex align-items-center gap-2 pt-2 border-top flex-wrap">
                                            <button type="button" class="btn btn-outline-success btn-sm" onclick="testMayarSpecific('production')">
                                                <i class="fa-solid fa-plug me-1"></i> Uji Koneksi Production
                                            </button>
                                            <span id="testProductionResult" class="small"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Webhook Callback URL Copyable Box Mayar -->
                        @php
                            $webhookUrl = url('/api/v1/payment/mayar/webhook/' . $store->slug);
                            $activeWebhookToken = ($curEnv === 'production' && !empty($store->mayar_webhook_token_production)) 
                                ? $store->mayar_webhook_token_production 
                                : $store->mayar_webhook_token;
                            if (!empty($activeWebhookToken)) {
                                $webhookUrl .= '?token=' . urlencode($activeWebhookToken);
                            }
                        @endphp
                        <div class="p-3 bg-white border rounded mb-3">
                            <label class="form-label small fw-bold text-dark mb-1 d-flex align-items-center justify-content-between">
                                <span><i class="fa-solid fa-bullhorn text-primary me-1"></i> Webhook Callback URL Toko Ini (Masukkan di Mayar.id):</span>
                                <span class="badge bg-light text-dark font-mono border">POST Callback</span>
                            </label>
                            <div class="input-group mb-2">
                                <input type="text" id="webhookUrlInput" class="form-control font-mono small bg-light" readonly value="{{ $webhookUrl }}">
                                <button type="button" class="btn btn-outline-primary" onclick="copyWebhookUrl()">
                                    <i class="fa-solid fa-copy me-1"></i> Salin URL
                                </button>
                                <a href="{{ url('/api/v1/payment/mayar/webhook/' . $store->slug) }}" target="_blank" class="btn btn-outline-secondary" title="Cek apakah endpoint aktif">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Cek Endpoint
                                </a>
                            </div>
                            <div class="p-2 rounded bg-light border small text-muted">
                                <i class="fa-solid fa-circle-check text-success me-1"></i>
                                <strong>Multi-Strategi Auto-Verifikasi:</strong> Endpoint ini otomatis mendeteksi Order berdasarkan <em>Candidate ID (Invoice/Transaction), Nomor Order (NR-PO-...), Slug Invoice Mayar, dan Kontak & Nominal Pelanggan</em>. Callback dari Sandbox maupun Production didukung otomatis.
                                @if(str_contains(url('/'), '127.0.0.1') || str_contains(url('/'), 'localhost'))
                                    <span class="d-block text-warning mt-1 fw-bold">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Catatan Localhost: Mayar.id membutuhkan URL berdomain publik/internet. Gunakan Ngrok atau pasang domain produksi Anda agar webhook dari Mayar dapat terkirim.
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. PENGATURAN KIRIM LANGSUNG KE RUMAH (KIRIMINAJA + GEOTAG OPENSTREETMAP) -->
            <hr class="my-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h6 class="fw-bold text-dark mb-1">
                        <i class="fa-solid fa-truck-fast text-primary me-1"></i>
                        3. Opsi Pengiriman KiriminAja (Kirim Langsung ke Rumah) & Geotag Toko
                    </h6>
                    <small class="text-muted">Aktifkan opsi kirim ke rumah untuk outlet ini menggunakan Select2 wilayah & pin peta OpenStreetMap.</small>
                </div>
                <span class="badge {{ ($kaEnv ?? 'sandbox') === 'production' ? 'bg-success' : 'bg-warning text-dark' }}">
                    KiriminAja: {{ strtoupper($kaEnv ?? 'SANDBOX') }}
                    {{ !empty($kaConfigured) ? '(API Key Aktif)' : '(API Key Belum Diisi)' }}
                </span>
            </div>

            <div class="p-3 border rounded-3 bg-light mb-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="deliveryEnabledSwitch"
                               name="delivery_enabled" value="1"
                               {{ old('delivery_enabled', $store->delivery_enabled) ? 'checked' : '' }}
                               onchange="toggleDeliverySettings(this.checked)">
                        <label class="form-check-label fw-bold text-dark" for="deliveryEnabledSwitch">
                            Aktifkan Opsi Kirim Langsung ke Rumah (KiriminAja) di Outlet Ini
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Jika diaktifkan, admin toko wajib melengkapi alamat asal (Provinsi, Kota, Kecamatan, Kelurahan), Kode Pos, Catatan Gang/Patokan, dan Geotag Peta.
                    </small>
                </div>
            </div>

            <div id="section_delivery_settings" class="{{ old('delivery_enabled', $store->delivery_enabled) ? '' : 'd-none' }}">
                <div class="p-3 border rounded-3 bg-white mb-4 shadow-sm">
                    @if(empty($kaConfigured))
                        <div class="alert alert-warning small py-2 mb-3">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>
                            <strong>Perhatian:</strong> <code>KIRIMINAJA_API_KEY</code> di file <code>.env</code> belum diisi. Dropdown wilayah & cek ongkir membutuhkan API Key KiriminAja yang valid.
                        </div>
                    @endif

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Provinsi Asal Toko <span class="text-danger">*</span></label>
                            <select name="ka_province_id" id="selectKaProvince" class="form-select ka-delivery-required">
                                @if(old('ka_province_id', $store->ka_province_id))
                                    <option value="{{ old('ka_province_id', $store->ka_province_id) }}" selected>
                                        {{ old('ka_province_name', $store->ka_province_name) }}
                                    </option>
                                @endif
                            </select>
                            <input type="hidden" name="ka_province_name" id="kaProvinceName" value="{{ old('ka_province_name', $store->ka_province_name) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kota / Kabupaten Asal <span class="text-danger">*</span></label>
                            <select name="ka_city_id" id="selectKaCity" class="form-select ka-delivery-required">
                                @if(old('ka_city_id', $store->ka_city_id))
                                    <option value="{{ old('ka_city_id', $store->ka_city_id) }}" selected>
                                        {{ old('ka_city_name', $store->ka_city_name) }}
                                    </option>
                                @endif
                            </select>
                            <input type="hidden" name="ka_city_name" id="kaCityName" value="{{ old('ka_city_name', $store->ka_city_name) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kecamatan Asal <span class="text-danger">*</span></label>
                            <select name="ka_district_id" id="selectKaDistrict" class="form-select ka-delivery-required">
                                @if(old('ka_district_id', $store->ka_district_id))
                                    <option value="{{ old('ka_district_id', $store->ka_district_id) }}" selected>
                                        {{ old('ka_district_name', $store->ka_district_name) }}
                                    </option>
                                @endif
                            </select>
                            <input type="hidden" name="ka_district_name" id="kaDistrictName" value="{{ old('ka_district_name', $store->ka_district_name) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Kelurahan / Desa Asal <span class="text-danger">*</span></label>
                            <select name="ka_subdistrict_id" id="selectKaSubdistrict" class="form-select ka-delivery-required">
                                @if(old('ka_subdistrict_id', $store->ka_subdistrict_id))
                                    <option value="{{ old('ka_subdistrict_id', $store->ka_subdistrict_id) }}" selected>
                                        {{ old('ka_subdistrict_name', $store->ka_subdistrict_name) }}
                                    </option>
                                @endif
                            </select>
                            <input type="hidden" name="ka_subdistrict_name" id="kaSubdistrictName" value="{{ old('ka_subdistrict_name', $store->ka_subdistrict_name) }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">Kode Pos <span class="text-danger">*</span></label>
                            <input type="text" name="ka_postal_code" id="kaPostalCode" class="form-control font-mono ka-delivery-required"
                                   maxlength="5" pattern="\d{5}" placeholder="12345"
                                   value="{{ old('ka_postal_code', $store->ka_postal_code) }}">
                        </div>

                        <div class="col-md-7">
                            <label class="form-label fw-semibold small">Alamat Lengkap Jalan / Ruko Toko (Min 10 karakter) <span class="text-danger">*</span></label>
                            <textarea name="ka_address" id="kaAddress" rows="2" class="form-control ka-delivery-required"
                                      minlength="10" placeholder="Jl. Contoh Raya No. 12, RT 01 / RW 02...">{{ old('ka_address', $store->ka_address) }}</textarea>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label fw-semibold small">Catatan Gang / Patokan Toko</label>
                            <textarea name="ka_address_note" id="kaAddressNote" rows="2" class="form-control"
                                      maxlength="150" placeholder="Contoh: Sebelah Alfamart, masuk gang mawar 20m pagar hitam...">{{ old('ka_address_note', $store->ka_address_note) }}</textarea>
                        </div>
                    </div>

                    <!-- Geotag Peta OpenStreetMap (NON-GMAPS) -->
                    <div class="border rounded-3 p-3 bg-light mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                            <label class="fw-bold text-dark small mb-0">
                                <i class="fa-solid fa-map-pin text-danger me-1"></i>
                                Geotag Lokasi Toko (OpenStreetMap - Klik & Geser Marker) <span class="text-danger">*</span>
                            </label>
                            <button type="button" class="btn btn-sm btn-outline-success fw-semibold" onclick="useStoreGpsLocation()">
                                <i class="fa-solid fa-crosshairs me-1"></i> Deteksi GPS Saya
                            </button>
                        </div>

                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" id="storeGeoSearchInput" class="form-control" placeholder="Cari lokasi di peta (mis: Jl. Sudirman Jakarta, Pasar Minggu)...">
                            <button type="button" class="btn btn-primary" onclick="searchStoreGeo()">Cari Lokasi</button>
                        </div>
                        <div id="storeGeoSearchResults" class="list-group mb-2 d-none" style="max-height: 150px; overflow-y: auto;"></div>

                        <div id="storeOriginMap" style="height: 300px; border-radius: 10px; border: 1px solid #cbd5e1; z-index: 1;"></div>

                        <div class="row g-2 mt-2">
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Latitude <span class="text-danger">*</span></label>
                                <input type="text" name="ka_latitude" id="kaLatitude" class="form-control form-control-sm font-mono bg-white ka-delivery-required"
                                       readonly value="{{ old('ka_latitude', $store->ka_latitude) }}" placeholder="-6.208800">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Longitude <span class="text-danger">*</span></label>
                                <input type="text" name="ka_longitude" id="kaLongitude" class="form-control form-control-sm font-mono bg-white ka-delivery-required"
                                       readonly value="{{ old('ka_longitude', $store->ka_longitude) }}" placeholder="106.845600">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">Alamat Terdeteksi Peta (Otomatis)</label>
                                <input type="text" name="ka_geo_address" id="kaGeoAddress" class="form-control form-control-sm bg-white"
                                       value="{{ old('ka_geo_address', $store->ka_geo_address) }}" placeholder="Klik atau geser marker di peta untuk mendeteksi alamat...">
                            </div>
                        </div>
                        <div id="storeGeoRegionHint" class="small text-info-emphasis mt-2 d-none"></div>
                    </div>

                    <!-- Konfigurasi Kurir & Dimensi Paket -->
                    <input type="hidden" name="delivery_couriers_submitted" value="1">
                    @php
                        $savedCouriers = old('delivery_couriers', $store->delivery_couriers ?? []);
                    @endphp
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold small mb-1">Kurir Diizinkan (Kosongkan = Semua Kurir Aktif)</label>
                            <div class="d-flex flex-wrap gap-3 p-2 border rounded bg-light">
                                <div>
                                    <span class="badge bg-primary-subtle text-primary me-1">Express:</span>
                                    @foreach(($defaultExpressCouriers ?? ['jne','jnt','sicepat','anteraja','ninja','sap','idx','tiki']) as $code)
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input delivery-courier-checkbox" type="checkbox" name="delivery_couriers[]"
                                                   id="courier_{{ $code }}" value="{{ $code }}"
                                                   {{ in_array($code, (array) $savedCouriers, true) ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold text-uppercase" for="courier_{{ $code }}">{{ $code }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <div>
                                    <span class="badge bg-success-subtle text-success me-1">Instant:</span>
                                    @foreach(($defaultInstantCouriers ?? ['gosend','grab_express','borzo']) as $code)
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input delivery-courier-checkbox" type="checkbox" name="delivery_couriers[]"
                                                   id="courier_{{ $code }}" value="{{ $code }}"
                                                   {{ in_array($code, (array) $savedCouriers, true) ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold text-uppercase" for="courier_{{ $code }}">{{ str_replace('_', ' ', $code) }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-6">
                            <label class="form-label small text-muted mb-1">Berat Default / Porsi (gram)</label>
                            <input type="number" name="delivery_weight_per_item" class="form-control form-control-sm font-mono"
                                   min="1" max="50000" value="{{ old('delivery_weight_per_item', $store->delivery_weight_per_item ?: 500) }}">
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small text-muted mb-1">Panjang Paket (cm)</label>
                            <input type="number" name="delivery_package_length" class="form-control form-control-sm font-mono"
                                   min="1" max="500" value="{{ old('delivery_package_length', $store->delivery_package_length ?: 20) }}">
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small text-muted mb-1">Lebar Paket (cm)</label>
                            <input type="number" name="delivery_package_width" class="form-control form-control-sm font-mono"
                                   min="1" max="500" value="{{ old('delivery_package_width', $store->delivery_package_width ?: 20) }}">
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small text-muted mb-1">Tinggi Paket (cm)</label>
                            <input type="number" name="delivery_package_height" class="form-control form-control-sm font-mono"
                                   min="1" max="500" value="{{ old('delivery_package_height', $store->delivery_package_height ?: 10) }}">
                        </div>
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

<!-- 4. SIMULASI BIAYA PENGIRIMAN KIRIMINAJA (SELECT2 SEARCHABLE) -->
<div class="card-custom mt-4">
    <div class="card-custom-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="card-custom-title mb-0">
            <i class="fa-solid fa-calculator text-success me-2"></i>
            4. Simulasi Biaya Pengiriman KiriminAja
        </h5>
        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" onclick="fetchKiriminAjaAccountInfo()">
            <i class="fa-solid fa-wallet me-1"></i> Cek Saldo KA Credit & Kurir Aktif
        </button>
    </div>
    <div class="p-4">
        <div id="kaAccountInfoBox" class="alert alert-info small d-none mb-3"></div>

        <p class="text-muted small mb-3">
            Uji tarif ongkos kirim <strong>Express</strong> &amp; <strong>Instant</strong> dari alamat asal toko (menggunakan data form di atas atau data tersimpan) ke wilayah tujuan menggunakan dropdown Select2 yang dapat dicari.
        </p>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold small">Provinsi Tujuan</label>
                <select id="simProvince" class="form-select"></select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold small">Kota / Kabupaten Tujuan</label>
                <select id="simCity" class="form-select"></select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold small">Kecamatan Tujuan</label>
                <select id="simDistrict" class="form-select"></select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold small">Kelurahan Tujuan</label>
                <select id="simSubdistrict" class="form-select"></select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Berat Total Paket (gram)</label>
                <input type="number" id="simWeight" class="form-control font-mono" value="1000" min="1" max="50000">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Estimasi Nilai Pesanan (Rp)</label>
                <input type="number" id="simItemValue" class="form-control font-mono" value="50000" min="0">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Koordinat Tujuan (Opsional utk Instant)</label>
                <div class="input-group input-group-sm">
                    <input type="text" id="simDestLat" class="form-control font-mono" placeholder="Lat (-6.2...)">
                    <input type="text" id="simDestLng" class="form-control font-mono" placeholder="Lng (106.8...)">
                </div>
            </div>
        </div>

        <div class="border rounded-3 p-3 bg-light mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <small class="fw-bold text-dark"><i class="fa-solid fa-map-location-dot text-danger me-1"></i> Pin Peta Tujuan Simulasi (OpenStreetMap — untuk Kurir Instant)</small>
                <small class="text-muted" id="simDestAddressPreview">Klik / geser marker untuk memilih koordinat tujuan</small>
            </div>
            <div class="input-group input-group-sm mb-2">
                <input type="text" id="simGeoSearchInput" class="form-control" placeholder="Cari lokasi tujuan di peta...">
                <button type="button" class="btn btn-outline-secondary" onclick="searchSimGeo()">Cari</button>
            </div>
            <div id="simGeoSearchResults" class="list-group mb-2 d-none" style="max-height: 120px; overflow-y: auto;"></div>
            <div id="simDestMap" style="height: 220px; border-radius: 8px; border: 1px solid #cbd5e1; z-index: 1;"></div>
            <input type="hidden" id="simDestAddress">
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <button type="button" id="btnSimulateShipping" class="btn btn-success fw-bold px-4" onclick="runShippingSimulation()">
                <i class="fa-solid fa-magnifying-glass-dollar me-1"></i> Hitung Simulasi Biaya Pengiriman
            </button>
            <span id="simLoadingIndicator" class="small text-muted d-none">
                <i class="fa-solid fa-spinner fa-spin me-1"></i> Menghitung tarif dari KiriminAja...
            </span>
        </div>

        <div id="simErrorBox" class="alert alert-danger small d-none"></div>
        <div id="simResultsBox" class="d-none"></div>
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

function toggleGatewayProvider(provider) {
    const boxMidtrans = document.getElementById('box_provider_midtrans');
    const boxMayar = document.getElementById('box_provider_mayar');
    const cardMid = document.getElementById('card_provider_midtrans');
    const cardMayar = document.getElementById('card_provider_mayar');

    if (provider === 'midtrans') {
        if (boxMidtrans) boxMidtrans.classList.remove('d-none');
        if (boxMayar) boxMayar.classList.add('d-none');

        if (cardMid) {
            cardMid.classList.add('border-primary', 'bg-primary', 'bg-opacity-10', 'shadow-sm');
            cardMid.classList.remove('border-light', 'bg-white');
        }
        if (cardMayar) {
            cardMayar.classList.remove('border-success', 'bg-success', 'bg-opacity-10', 'shadow-sm');
            cardMayar.classList.add('border-light', 'bg-white');
        }
    } else {
        if (boxMayar) boxMayar.classList.remove('d-none');
        if (boxMidtrans) boxMidtrans.classList.add('d-none');

        if (cardMayar) {
            cardMayar.classList.add('border-success', 'bg-success', 'bg-opacity-10', 'shadow-sm');
            cardMayar.classList.remove('border-light', 'bg-white');
        }
        if (cardMid) {
            cardMid.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10', 'shadow-sm');
            cardMid.classList.add('border-light', 'bg-white');
        }
    }
}

function handleMidtransEnvChange(env) {
    const cardSandbox = document.getElementById('card_mid_env_sandbox');
    const cardProd = document.getElementById('card_mid_env_production');
    const boxSandbox = document.getElementById('box_mid_sandbox_fields');
    const boxProd = document.getElementById('box_mid_production_fields');
    const tagSandbox = document.getElementById('tag_mid_sandbox_active');
    const tagProd = document.getElementById('tag_mid_production_active');
    const badge = document.getElementById('midtransEnvBadge');

    if (env === 'sandbox') {
        if (cardSandbox) {
            cardSandbox.classList.add('border-warning', 'bg-warning', 'bg-opacity-10', 'shadow-sm');
            cardSandbox.classList.remove('border-light', 'bg-light');
        }
        if (cardProd) {
            cardProd.classList.remove('border-success', 'bg-success', 'bg-opacity-10', 'shadow-sm');
            cardProd.classList.add('border-light', 'bg-light');
        }
        if (boxSandbox) boxSandbox.classList.add('border-warning', 'shadow-sm');
        if (boxProd) boxProd.classList.remove('border-success', 'shadow-sm');
        if (tagSandbox) {
            tagSandbox.className = 'badge bg-warning text-dark';
            tagSandbox.textContent = 'Aktif Dipakai';
        }
        if (tagProd) {
            tagProd.className = 'badge bg-light text-muted border';
            tagProd.textContent = 'Standby';
        }
        if (badge) {
            badge.className = 'badge bg-warning text-dark';
            badge.textContent = 'Mode Aktif: SANDBOX';
        }
    } else {
        if (cardProd) {
            cardProd.classList.add('border-success', 'bg-success', 'bg-opacity-10', 'shadow-sm');
            cardProd.classList.remove('border-light', 'bg-light');
        }
        if (cardSandbox) {
            cardSandbox.classList.remove('border-warning', 'bg-warning', 'bg-opacity-10', 'shadow-sm');
            cardSandbox.classList.add('border-light', 'bg-light');
        }
        if (boxProd) boxProd.classList.add('border-success', 'shadow-sm');
        if (boxSandbox) boxSandbox.classList.remove('border-warning', 'shadow-sm');
        if (tagProd) {
            tagProd.className = 'badge bg-success';
            tagProd.textContent = 'Aktif Dipakai';
        }
        if (tagSandbox) {
            tagSandbox.className = 'badge bg-light text-muted border';
            tagSandbox.textContent = 'Standby';
        }
        if (badge) {
            badge.className = 'badge bg-success';
            badge.textContent = 'Mode Aktif: PRODUCTION';
        }
    }
}

async function testMidtransSpecific(env) {
    const isProd = env === 'production';
    const serverKeyInput = isProd
        ? document.getElementById('midtransServerKeyProduction')
        : document.getElementById('midtransServerKey');
    const clientKeyInput = isProd
        ? document.getElementById('midtransClientKeyProduction')
        : document.getElementById('midtransClientKey');

    const serverKey = serverKeyInput ? serverKeyInput.value.trim() : '';
    const clientKey = clientKeyInput ? clientKeyInput.value.trim() : '';
    const resultSpan = document.getElementById(isProd ? 'testMidProductionResult' : 'testMidSandboxResult');

    if (!serverKey) {
        if (resultSpan) {
            resultSpan.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-circle-exclamation me-1"></i> Masukkan Server Key ' + (isProd ? 'Production' : 'Sandbox') + ' terlebih dahulu.</span>';
        }
        return;
    }

    if (resultSpan) {
        resultSpan.innerHTML = '<span class="text-muted"><i class="fa-solid fa-spinner fa-spin me-1"></i> Menguji koneksi ' + (isProd ? 'Production' : 'Sandbox') + '...</span>';
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch("{{ route('store.backend.settings.testMidtrans', $store->slug) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                server_key: serverKey,
                client_key: clientKey,
                environment: env
            })
        });

        const data = await res.json();
        if (data.success) {
            resultSpan.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> ' + (data.message || 'Koneksi Berhasil!') + '</span>';
        } else {
            resultSpan.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> ' + (data.message || 'Koneksi Gagal') + '</span>';
        }
    } catch (err) {
        if (resultSpan) {
            resultSpan.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> Gagal: ' + err.message + '</span>';
        }
    }
}

function copyMidtransWebhookUrl() {
    const input = document.getElementById('midtransWebhookUrlInput');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        alert('Notification Webhook URL Midtrans berhasil disalin ke clipboard!');
    }).catch(() => {
        document.execCommand('copy');
        alert('Notification Webhook URL Midtrans berhasil disalin!');
    });
}

// Global simulation variables
let currentSimData = null;

async function startMidtransSimulation() {
    const amount = document.getElementById('simAmount').value || 10000;
    const btn = document.getElementById('btnStartSim');
    const resultBox = document.getElementById('simResultBox');
    const orderIdElem = document.getElementById('simOrderId');
    const tokenElem = document.getElementById('simSnapToken');
    const badge = document.getElementById('simStatusBadge');
    const msg = document.getElementById('simCheckMessage');

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses...';
    if (msg) msg.textContent = '';

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch("{{ route('store.backend.settings.testMidtransTransaction', $store->slug) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ amount: parseInt(amount) })
        });

        const data = await res.json();
        if (data.success) {
            currentSimData = data;
            resultBox.classList.remove('d-none');
            orderIdElem.textContent = data.order_id;
            tokenElem.textContent = data.snap_token;
            badge.className = 'badge bg-warning text-dark';
            badge.textContent = 'Status: PENDING';

            // Auto-open payment in new window / tab
            if (data.payment_url) {
                window.open(data.payment_url, '_blank', 'width=800,height=700');
            }
        } else {
            alert('Gagal membuat transaksi simulasi: ' + (data.message || 'Pastikan Server Key & Client Key sudah diisi dan disimpan.'));
        }
    } catch (err) {
        alert('Terjadi kesalahan: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-play me-1"></i> Buat Transaksi Uji Coba';
    }
}

function openSimPayment() {
    if (!currentSimData || !currentSimData.payment_url) {
        alert('Belum ada transaksi simulasi aktif. Silakan klik tombol "Buat Transaksi Uji Coba".');
        return;
    }
    window.open(currentSimData.payment_url, '_blank', 'width=800,height=700');
}

async function checkSimulationStatus() {
    if (!currentSimData || !currentSimData.order_id) {
        alert('Belum ada transaksi simulasi aktif.');
        return;
    }

    const msg = document.getElementById('simCheckMessage');
    const badge = document.getElementById('simStatusBadge');
    if (msg) msg.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Mengecek status ke Midtrans...';

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch("{{ route('store.backend.settings.testMidtransStatus', $store->slug) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                order_id: currentSimData.order_id,
                environment: currentSimData.environment || 'sandbox'
            })
        });

        const data = await res.json();
        if (data.success) {
            if (data.is_paid) {
                badge.className = 'badge bg-success';
                badge.textContent = 'Status: LUNAS (' + data.transaction_status.toUpperCase() + ')';
                if (msg) msg.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> Pembayaran Terverifikasi LUNAS! Transaksi Snap berhasil penuh.</span>';
            } else {
                badge.className = 'badge bg-warning text-dark';
                badge.textContent = 'Status: ' + data.transaction_status.toUpperCase();
                if (msg) msg.innerHTML = '<span class="text-muted"><i class="fa-solid fa-clock me-1"></i> Belum dibayar (status: ' + data.transaction_status + '). Silakan selesaikan via Simulator.</span>';
            }
        } else {
            if (msg) msg.innerHTML = '<span class="text-danger">' + (data.message || 'Gagal mengecek status') + '</span>';
        }
    } catch (err) {
        if (msg) msg.innerHTML = '<span class="text-danger">Error: ' + err.message + '</span>';
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
        alert('Webhook URL Mayar berhasil disalin ke clipboard!');
    }).catch(() => {
        document.execCommand('copy');
        alert('Webhook URL Mayar berhasil disalin!');
    });
}

function handleEnvChange(env) {
    const cardSandbox = document.getElementById('card_env_sandbox');
    const cardProd = document.getElementById('card_env_production');
    const boxSandbox = document.getElementById('box_sandbox_fields');
    const boxProd = document.getElementById('box_production_fields');
    const tagSandbox = document.getElementById('tag_sandbox_active');
    const tagProd = document.getElementById('tag_production_active');
    const badgeStatus = document.getElementById('envStatusBadge');

    if (env === 'sandbox') {
        if (cardSandbox) {
            cardSandbox.classList.add('border-warning', 'bg-warning', 'bg-opacity-10', 'shadow-sm');
            cardSandbox.classList.remove('border-light', 'bg-light');
        }
        if (cardProd) {
            cardProd.classList.remove('border-success', 'bg-success', 'bg-opacity-10', 'shadow-sm');
            cardProd.classList.add('border-light', 'bg-light');
        }
        if (boxSandbox) boxSandbox.classList.add('border-warning', 'shadow-sm');
        if (boxProd) boxProd.classList.remove('border-success', 'shadow-sm');
        if (tagSandbox) {
            tagSandbox.className = 'badge bg-warning text-dark';
            tagSandbox.textContent = 'Aktif Dipakai';
        }
        if (tagProd) {
            tagProd.className = 'badge bg-light text-muted border';
            tagProd.textContent = 'Standby';
        }
        if (badgeStatus) {
            badgeStatus.className = 'badge bg-warning text-dark';
            badgeStatus.textContent = 'Mode Aktif: SANDBOX';
        }
    } else {
        if (cardProd) {
            cardProd.classList.add('border-success', 'bg-success', 'bg-opacity-10', 'shadow-sm');
            cardProd.classList.remove('border-light', 'bg-light');
        }
        if (cardSandbox) {
            cardSandbox.classList.remove('border-warning', 'bg-warning', 'bg-opacity-10', 'shadow-sm');
            cardSandbox.classList.add('border-light', 'bg-light');
        }
        if (boxProd) boxProd.classList.add('border-success', 'shadow-sm');
        if (boxSandbox) boxSandbox.classList.remove('border-warning', 'shadow-sm');
        if (tagProd) {
            tagProd.className = 'badge bg-success';
            tagProd.textContent = 'Aktif Dipakai';
        }
        if (tagSandbox) {
            tagSandbox.className = 'badge bg-light text-muted border';
            tagSandbox.textContent = 'Standby';
        }
        if (badgeStatus) {
            badgeStatus.className = 'badge bg-success';
            badgeStatus.textContent = 'Mode Aktif: PRODUCTION';
        }
    }
}

async function testMayarSpecific(env) {
    const isProd = env === 'production';
    const apiKeyInput = isProd 
        ? document.getElementById('mayarApiKeyProduction') 
        : document.getElementById('mayarApiKey');
    const apiKey = apiKeyInput ? apiKeyInput.value.trim() : '';
    const resultSpan = document.getElementById(isProd ? 'testProductionResult' : 'testSandboxResult');

    if (!apiKey) {
        if (resultSpan) {
            resultSpan.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-circle-exclamation me-1"></i> Masukkan API Key ' + (isProd ? 'Production' : 'Sandbox') + ' terlebih dahulu.</span>';
        }
        return;
    }

    if (resultSpan) {
        resultSpan.innerHTML = '<span class="text-muted"><i class="fa-solid fa-spinner fa-spin me-1"></i> Menguji ' + (isProd ? 'Production' : 'Sandbox') + '...</span>';
    }

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
            resultSpan.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i> ' + (data.message || 'Koneksi Berhasil!') + '</span>';
        } else {
            resultSpan.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> ' + (data.message || 'Koneksi Gagal') + '</span>';
        }
    } catch (err) {
        console.error('Test Mayar error', err);
        if (resultSpan) {
            resultSpan.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> Gagal: ' + err.message + '</span>';
        }
    }
}
</script>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const KA_PROVINCE_CACHE = { list: null };
    let originMapInstance = null;
    let originMarkerInstance = null;
    let simMapInstance = null;
    let simMarkerInstance = null;

    function syncDeliveryRequired(enabled) {
        document.querySelectorAll('.ka-delivery-required').forEach(function (el) {
            if (enabled) {
                el.setAttribute('required', 'required');
            } else {
                el.removeAttribute('required');
            }
        });
    }

    window.toggleDeliverySettings = function (enabled) {
        const box = document.getElementById('section_delivery_settings');
        if (box) {
            box.classList.toggle('d-none', !enabled);
        }
        syncDeliveryRequired(enabled);
        if (enabled && originMapInstance) {
            setTimeout(function () { originMapInstance.invalidateSize(); }, 200);
        }
    };

    async function fetchJson(url) {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.message || 'Gagal memuat data');
        }
        return data.data || [];
    }

    function populateSelect2($el, items, placeholder, selectedId, extraMapper) {
        const currentVal = selectedId !== undefined && selectedId !== null ? String(selectedId) : '';
        $el.empty().append(new Option(placeholder, '', !currentVal, !currentVal));
        (items || []).forEach(function (item) {
            const isSel = currentVal && String(item.id) === currentVal;
            const opt = new Option(item.name, item.id, isSel, isSel);
            if (extraMapper) {
                extraMapper(opt, item);
            }
            $el.append(opt);
        });
        $el.trigger('change.select2');
    }

    async function getProvinces() {
        if (!KA_PROVINCE_CACHE.list) {
            KA_PROVINCE_CACHE.list = await fetchJson('/api/v1/shipping/provinces');
        }
        return KA_PROVINCE_CACHE.list;
    }

    function createRegionCascade(config) {
        const $prov = $(config.province);
        const $city = $(config.city);
        const $dist = $(config.district);
        const $sub = $(config.subdistrict);

        [$prov, $city, $dist, $sub].forEach(function ($s, idx) {
            const labels = ['-- Pilih Provinsi --', '-- Pilih Kota/Kab --', '-- Pilih Kecamatan --', '-- Pilih Kelurahan --'];
            $s.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: labels[idx],
                allowClear: true
            });
        });

        async function loadInitial() {
            const initProv = config.initial?.provinceId || '';
            const initCity = config.initial?.cityId || '';
            const initDist = config.initial?.districtId || '';
            const initSub = config.initial?.subdistrictId || '';

            try {
                const provinces = await getProvinces();
                populateSelect2($prov, provinces, '-- Pilih Provinsi --', initProv);
                if (initProv) {
                    const cities = await fetchJson('/api/v1/shipping/cities?province_id=' + initProv);
                    populateSelect2($city, cities, '-- Pilih Kota/Kab --', initCity, function (opt, item) {
                        if (item.postal_code) $(opt).attr('data-postal', item.postal_code);
                    });
                }
                if (initCity) {
                    const districts = await fetchJson('/api/v1/shipping/districts?city_id=' + initCity);
                    populateSelect2($dist, districts, '-- Pilih Kecamatan --', initDist);
                }
                if (initDist) {
                    const subdistricts = await fetchJson('/api/v1/shipping/subdistricts?district_id=' + initDist);
                    populateSelect2($sub, subdistricts, '-- Pilih Kelurahan --', initSub);
                }
            } catch (err) {
                console.warn('Region init warning:', err.message);
            }
        }

        $prov.on('select2:select select2:clear', async function () {
            const val = $prov.val();
            const text = val ? $prov.find('option:selected').text().trim() : '';
            if (config.provinceNameInput) $(config.provinceNameInput).val(text);
            if (config.cityNameInput) $(config.cityNameInput).val('');
            if (config.districtNameInput) $(config.districtNameInput).val('');
            if (config.subdistrictNameInput) $(config.subdistrictNameInput).val('');

            populateSelect2($city, [], '-- Pilih Kota/Kab --', '');
            populateSelect2($dist, [], '-- Pilih Kecamatan --', '');
            populateSelect2($sub, [], '-- Pilih Kelurahan --', '');

            if (!val) return;
            try {
                const cities = await fetchJson('/api/v1/shipping/cities?province_id=' + val);
                populateSelect2($city, cities, '-- Pilih Kota/Kab --', '', function (opt, item) {
                    if (item.postal_code) $(opt).attr('data-postal', item.postal_code);
                });
            } catch (e) {
                alert(e.message);
            }
        });

        $city.on('select2:select select2:clear', async function () {
            const val = $city.val();
            const $opt = $city.find('option:selected');
            const text = val ? $opt.text().trim() : '';
            if (config.cityNameInput) $(config.cityNameInput).val(text);
            if (config.districtNameInput) $(config.districtNameInput).val('');
            if (config.subdistrictNameInput) $(config.subdistrictNameInput).val('');

            const postal = $opt.attr('data-postal');
            if (postal && config.postalInput && !$(config.postalInput).val()) {
                $(config.postalInput).val(postal);
            }

            populateSelect2($dist, [], '-- Pilih Kecamatan --', '');
            populateSelect2($sub, [], '-- Pilih Kelurahan --', '');

            if (!val) return;
            try {
                const districts = await fetchJson('/api/v1/shipping/districts?city_id=' + val);
                populateSelect2($dist, districts, '-- Pilih Kecamatan --', '');
            } catch (e) {
                alert(e.message);
            }
        });

        $dist.on('select2:select select2:clear', async function () {
            const val = $dist.val();
            const text = val ? $dist.find('option:selected').text().trim() : '';
            if (config.districtNameInput) $(config.districtNameInput).val(text);
            if (config.subdistrictNameInput) $(config.subdistrictNameInput).val('');

            populateSelect2($sub, [], '-- Pilih Kelurahan --', '');

            if (!val) return;
            try {
                const subdistricts = await fetchJson('/api/v1/shipping/subdistricts?district_id=' + val);
                populateSelect2($sub, subdistricts, '-- Pilih Kelurahan --', '');
            } catch (e) {
                alert(e.message);
            }
        });

        $sub.on('select2:select select2:clear', function () {
            const val = $sub.val();
            const text = val ? $sub.find('option:selected').text().trim() : '';
            if (config.subdistrictNameInput) $(config.subdistrictNameInput).val(text);
        });

        async function applyMatchedRegion(m) {
            if (!m || !m.province_id) return;
            try {
                const provinces = await getProvinces();
                populateSelect2($prov, provinces, '-- Pilih Provinsi --', m.province_id);
                if (config.provinceNameInput) $(config.provinceNameInput).val(m.province_name || '');

                if (m.city_id) {
                    const cities = await fetchJson('/api/v1/shipping/cities?province_id=' + m.province_id);
                    populateSelect2($city, cities, '-- Pilih Kota/Kab --', m.city_id);
                    if (config.cityNameInput) $(config.cityNameInput).val(m.city_name || '');
                }
                if (m.district_id) {
                    const districts = await fetchJson('/api/v1/shipping/districts?city_id=' + m.city_id);
                    populateSelect2($dist, districts, '-- Pilih Kecamatan --', m.district_id);
                    if (config.districtNameInput) $(config.districtNameInput).val(m.district_name || '');
                }
                if (m.subdistrict_id) {
                    const subdistricts = await fetchJson('/api/v1/shipping/subdistricts?district_id=' + m.district_id);
                    populateSelect2($sub, subdistricts, '-- Pilih Kelurahan --', m.subdistrict_id);
                    if (config.subdistrictNameInput) $(config.subdistrictNameInput).val(m.subdistrict_name || '');
                }
            } catch (e) {
                alert('Gagal menerapkan wilayah: ' + e.message);
            }
        }

        loadInitial();
        return { applyMatchedRegion };
    }

    function createLeafletPicker(opts) {
        const el = document.getElementById(opts.mapId);
        if (!el || typeof L === 'undefined') return null;

        const hasInit = opts.initLat !== null && opts.initLat !== '' && opts.initLng !== null && opts.initLng !== '';
        const centerLat = hasInit ? parseFloat(opts.initLat) : -6.2088;
        const centerLng = hasInit ? parseFloat(opts.initLng) : 106.8456;

        const map = L.map(el, { center: [centerLat, centerLng], zoom: hasInit ? 16 : 13 });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        let marker = null;

        function placeMarker(lat, lng, center) {
            if (!marker) {
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.on('dragend', function () {
                    const pos = marker.getLatLng();
                    onCoordsPicked(pos.lat, pos.lng);
                });
            } else {
                marker.setLatLng([lat, lng]);
            }
            if (center) {
                map.setView([lat, lng], Math.max(map.getZoom() || 15, 16));
            }
        }

        async function onCoordsPicked(lat, lng) {
            const fixedLat = Number(lat).toFixed(7);
            const fixedLng = Number(lng).toFixed(7);
            $(opts.latInput).val(fixedLat);
            $(opts.lngInput).val(fixedLng);
            placeMarker(lat, lng, false);

            try {
                const res = await fetch('/api/v1/geo/reverse?latitude=' + fixedLat + '&longitude=' + fixedLng + '&match_region=' + (opts.matchRegion ? '1' : '0'), {
                    headers: { 'Accept': 'application/json' }
                });
                const json = await res.json();
                if (json.success && json.data) {
                    const d = json.data;
                    if (opts.onReverse) opts.onReverse(d, fixedLat, fixedLng);
                }
            } catch (e) {}
        }

        if (hasInit) {
            placeMarker(centerLat, centerLng, false);
        }

        map.on('click', function (e) {
            placeMarker(e.latlng.lat, e.latlng.lng, false);
            onCoordsPicked(e.latlng.lat, e.latlng.lng);
        });

        return {
            map,
            setPoint: function (lat, lng) {
                placeMarker(lat, lng, true);
                onCoordsPicked(lat, lng);
            }
        };
    }

    let storeCascade = null;
    let originPicker = null;
    let simPicker = null;
    let lastMatchedRegion = null;

    $(document).ready(function () {
        const initialEnabled = document.getElementById('deliveryEnabledSwitch')?.checked || false;
        syncDeliveryRequired(initialEnabled);

        storeCascade = createRegionCascade({
            province: '#selectKaProvince',
            city: '#selectKaCity',
            district: '#selectKaDistrict',
            subdistrict: '#selectKaSubdistrict',
            provinceNameInput: '#kaProvinceName',
            cityNameInput: '#kaCityName',
            districtNameInput: '#kaDistrictName',
            subdistrictNameInput: '#kaSubdistrictName',
            postalInput: '#kaPostalCode',
            initial: {
                provinceId: @json(old('ka_province_id', $store->ka_province_id)),
                cityId: @json(old('ka_city_id', $store->ka_city_id)),
                districtId: @json(old('ka_district_id', $store->ka_district_id)),
                subdistrictId: @json(old('ka_subdistrict_id', $store->ka_subdistrict_id)),
            }
        });

        createRegionCascade({
            province: '#simProvince',
            city: '#simCity',
            district: '#simDistrict',
            subdistrict: '#simSubdistrict',
            initial: {}
        });

        originPicker = createLeafletPicker({
            mapId: 'storeOriginMap',
            latInput: '#kaLatitude',
            lngInput: '#kaLongitude',
            initLat: @json(old('ka_latitude', $store->ka_latitude)),
            initLng: @json(old('ka_longitude', $store->ka_longitude)),
            matchRegion: true,
            onReverse: function (d) {
                $('#kaGeoAddress').val(d.display_name || '');
                if (d.postal_code && !$('#kaPostalCode').val()) {
                    $('#kaPostalCode').val(d.postal_code);
                }
                if (d.short_address && !$('#kaAddress').val()) {
                    $('#kaAddress').val(d.short_address);
                }
                if (d.matched_region && d.matched_region.province_id) {
                    lastMatchedRegion = d.matched_region;
                    const parts = [d.matched_region.subdistrict_name, d.matched_region.district_name, d.matched_region.city_name, d.matched_region.province_name].filter(Boolean).join(', ');
                    $('#storeGeoRegionHint')
                        .removeClass('d-none')
                        .html('<i class="fa-solid fa-wand-magic-sparkles me-1"></i> Wilayah KiriminAja terdeteksi: <strong>' + parts + '</strong> <button type="button" class="btn btn-link btn-sm p-0 ms-2 fw-bold" onclick="applyDetectedStoreRegion()">Terapkan ke Dropdown</button>');
                }
            }
        });
        originMapInstance = originPicker ? originPicker.map : null;

        simPicker = createLeafletPicker({
            mapId: 'simDestMap',
            latInput: '#simDestLat',
            lngInput: '#simDestLng',
            initLat: null,
            initLng: null,
            matchRegion: false,
            onReverse: function (d) {
                $('#simDestAddress').val(d.display_name || '');
                $('#simDestAddressPreview').text(d.display_name || 'Koordinat dipilih');
            }
        });
        simMapInstance = simPicker ? simPicker.map : null;

        $('#storeGeoSearchInput').on('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); window.searchStoreGeo(); }
        });
        $('#simGeoSearchInput').on('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); window.searchSimGeo(); }
        });

        if (initialEnabled && originMapInstance) {
            setTimeout(function () { originMapInstance.invalidateSize(); }, 250);
        }
    });

    window.applyDetectedStoreRegion = function () {
        if (lastMatchedRegion && storeCascade) {
            storeCascade.applyMatchedRegion(lastMatchedRegion);
        }
    };

    window.useStoreGpsLocation = function () {
        if (!navigator.geolocation) {
            alert('Browser tidak mendukung GPS.');
            return;
        }
        navigator.geolocation.getCurrentPosition(
            function (pos) {
                if (originPicker) originPicker.setPoint(pos.coords.latitude, pos.coords.longitude);
            },
            function () {
                alert('Gagal mendeteksi lokasi GPS. Klik langsung pada peta untuk menentukan titik toko.');
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    };

    async function performGeoSearch(queryInputId, resultsId, picker) {
        const q = $(queryInputId).val().trim();
        if (q.length < 2) return;
        const $box = $(resultsId);
        $box.removeClass('d-none').html('<div class="list-group-item small text-muted"><i class="fa-solid fa-spinner fa-spin me-1"></i> Mencari di OpenStreetMap...</div>');
        try {
            const items = await fetchJson('/api/v1/geo/search?q=' + encodeURIComponent(q));
            $box.empty();
            if (!items.length) {
                $box.html('<div class="list-group-item small text-muted">Lokasi tidak ditemukan.</div>');
                return;
            }
            items.forEach(function (item) {
                const $btn = $('<button type="button" class="list-group-item list-group-item-action small py-2 text-start"></button>');
                $btn.html('<i class="fa-solid fa-location-dot text-danger me-1"></i> ' + $('<div>').text(item.display_name).html());
                $btn.on('click', function () {
                    $box.addClass('d-none');
                    if (picker) picker.setPoint(item.latitude, item.longitude);
                });
                $box.append($btn);
            });
        } catch (err) {
            $box.html('<div class="list-group-item small text-danger">' + err.message + '</div>');
        }
    }

    window.searchStoreGeo = function () {
        performGeoSearch('#storeGeoSearchInput', '#storeGeoSearchResults', originPicker);
    };

    window.searchSimGeo = function () {
        performGeoSearch('#simGeoSearchInput', '#simGeoSearchResults', simPicker);
    };

    window.runShippingSimulation = async function () {
        const districtId = $('#simDistrict').val();
        if (!districtId) {
            alert('Pilih Provinsi, Kota, dan Kecamatan tujuan terlebih dahulu.');
            return;
        }

        const $err = $('#simErrorBox').addClass('d-none').text('');
        const $resBox = $('#simResultsBox').addClass('d-none').empty();
        $('#simLoadingIndicator').removeClass('d-none');
        $('#btnSimulateShipping').prop('disabled', true);

        const selectedCouriers = [];
        document.querySelectorAll('.delivery-courier-checkbox:checked').forEach(function (cb) {
            selectedCouriers.push(cb.value);
        });

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res = await fetch("{{ route('store.backend.settings.simulateShipping', $store->slug) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    destination_district_id: parseInt(districtId, 10),
                    destination_subdistrict_id: $('#simSubdistrict').val() ? parseInt($('#simSubdistrict').val(), 10) : null,
                    destination_latitude: $('#simDestLat').val() || null,
                    destination_longitude: $('#simDestLng').val() || null,
                    destination_address: $('#simDestAddress').val() || null,
                    weight_grams: parseInt($('#simWeight').val() || '1000', 10),
                    item_value: parseFloat($('#simItemValue').val() || '50000'),
                    origin_district_id: $('#selectKaDistrict').val() ? parseInt($('#selectKaDistrict').val(), 10) : null,
                    origin_subdistrict_id: $('#selectKaSubdistrict').val() ? parseInt($('#selectKaSubdistrict').val(), 10) : null,
                    origin_postal_code: $('#kaPostalCode').val() || null,
                    origin_address: $('#kaAddress').val() || null,
                    origin_province_name: $('#kaProvinceName').val() || null,
                    origin_city_name: $('#kaCityName').val() || null,
                    origin_district_name: $('#kaDistrictName').val() || null,
                    origin_subdistrict_name: $('#kaSubdistrictName').val() || null,
                    origin_latitude: $('#kaLatitude').val() || null,
                    origin_longitude: $('#kaLongitude').val() || null,
                    delivery_couriers: selectedCouriers
                })
            });

            const json = await res.json();
            if (!res.ok || !json.success) {
                $err.removeClass('d-none').html('<i class="fa-solid fa-circle-exclamation me-1"></i> ' + (json.message || 'Gagal menghitung simulasi tarif.'));
                return;
            }

            const data = json.data || {};
            const options = data.options || [];
            let html = '<div class="small text-muted mb-2"><strong>Asal:</strong> ' + $('<div>').text(data.origin || '-').html() + ' · <strong>Berat:</strong> ' + (data.weight_grams || 0) + 'g</div>';

            if (data.errors && data.errors.length) {
                html += '<div class="alert alert-warning py-2 small mb-2">' + data.errors.map(function (e) { return $('<div>').text(e).html(); }).join('<br>') + '</div>';
            }

            if (!options.length) {
                html += '<div class="alert alert-secondary small">Tidak ada tarif kurir yang tersedia untuk rute ini.</div>';
            } else {
                html += '<div class="table-responsive"><table class="table table-sm table-hover align-middle border"><thead class="table-light"><tr><th>Tipe</th><th>Kurir</th><th>Layanan</th><th>Estimasi</th><th class="text-end">Ongkos Kirim</th></tr></thead><tbody>';
                options.forEach(function (opt) {
                    const badge = opt.type === 'instant'
                        ? '<span class="badge bg-success">INSTANT</span>'
                        : '<span class="badge bg-primary">EXPRESS</span>';
                    const costFormatted = new Intl.NumberFormat('id-ID').format(opt.cost || 0);
                    html += '<tr>'
                        + '<td>' + badge + '</td>'
                        + '<td class="fw-bold text-dark">' + $('<div>').text(opt.courier_name || opt.courier).html() + '</td>'
                        + '<td>' + $('<div>').text(opt.service_name || opt.service_type).html() + ' <small class="text-muted font-mono">(' + $('<div>').text(opt.service_type || '').html() + ')</small></td>'
                        + '<td class="small text-muted">' + $('<div>').text(opt.etd || '-').html() + '</td>'
                        + '<td class="text-end font-mono fw-bold text-success">Rp ' + costFormatted + '</td>'
                        + '</tr>';
                });
                html += '</tbody></table></div>';
            }
            $resBox.removeClass('d-none').html(html);
        } catch (err) {
            $err.removeClass('d-none').html('<i class="fa-solid fa-circle-xmark me-1"></i> ' + err.message);
        } finally {
            $('#simLoadingIndicator').addClass('d-none');
            $('#btnSimulateShipping').prop('disabled', false);
        }
    };

    window.fetchKiriminAjaAccountInfo = async function () {
        const $box = $('#kaAccountInfoBox');
        $box.removeClass('d-none alert-danger').addClass('alert-info').html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menghubungi KiriminAja...');
        try {
            const data = await fetchJson("{{ route('store.backend.settings.kiriminajaInfo', $store->slug) }}");
            const bal = data.balance || {};
            const balText = bal.error
                ? '<span class="text-warning">' + bal.error + '</span>'
                : ('Saldo: <strong>Rp ' + new Intl.NumberFormat('id-ID').format(bal.balance || 0) + '</strong> (Pending: Rp ' + new Intl.NumberFormat('id-ID').format(bal.pending || 0) + ')');
            const couriers = (data.couriers || []).map(function (c) { return c.name || c.code; }).join(', ');
            $box.html('<strong>Mode:</strong> ' + (data.environment || 'sandbox').toUpperCase() + ' · ' + balText + '<br><strong>Kurir Aktif (' + (data.couriers || []).length + '):</strong> ' + (couriers || '-'));
        } catch (err) {
            $box.removeClass('alert-info').addClass('alert-danger').html('<i class="fa-solid fa-circle-xmark me-1"></i> ' + err.message);
        }
    };
})();
</script>
@endpush

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
