<template>
  <AppLayout>
    <div class="trace-page container py-5">
      <div class="trace-header text-center mb-5">
        <span class="badge badge-primary mb-2">LIVE ORDER TRACKING</span>
        <h1 class="page-title">Lacak Status Pesanan Anda</h1>
        <p class="text-muted">Pantau tahapan memasak dapur hingga pengantaran secara real-time.</p>

        <!-- Search / Change Order Number Input -->
        <div class="search-box mx-auto mt-3">
          <input type="text" v-model="orderSearchInput" placeholder="Masukkan Nomor Order (cth: NR-PO-261012-001)" 
                 class="form-input font-mono text-uppercase" @keyup.enter="handleSearch" />
          <button class="btn btn-primary" @click="handleSearch">
            <i class="fa-solid fa-radar"></i> Lacak
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-custom"></div>
        <p class="text-muted mt-3">Menghubungkan ke sistem dapur Naumil...</p>
      </div>

      <!-- Not Found State -->
      <div v-else-if="notFound" class="card text-center p-5 max-w-600 mx-auto">
        <i class="fa-solid fa-magnifying-glass-chart fa-3x text-muted mb-3"></i>
        <h4 class="fw-bold">Pesanan Tidak Ditemukan</h4>
        <p class="text-muted">Pastikan nomor pesanan yang Anda masukkan benar, atau cek kembali pesan konfirmasi WhatsApp Anda.</p>
      </div>

      <!-- Active Tracing Screen -->
      <div v-else-if="orderData" class="trace-content max-w-800 mx-auto">
        <!-- Order Info Header Card -->
        <div class="card p-4 mb-4">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
              <span class="badge badge-dark mb-1 font-mono">{{ orderData.order?.store?.name }}</span>
              <h2 class="fw-bold font-mono text-primary mb-0 fs-3">{{ orderData.order?.order_number }}</h2>
              <small class="text-muted">Pemesan: <strong>{{ orderData.order?.client?.name }}</strong> &bull; Dipesan pada {{ formatDate(orderData.order?.created_at) }}</small>
            </div>

            <div class="text-end">
              <div class="d-flex align-items-center justify-content-end gap-2 flex-wrap mb-1">
                <span :class="['badge', getStatusBadgeClass(orderData.order?.status), 'fs-6 p-2']">
                  <i class="fa-solid fa-circle-dot live-pulse me-1"></i> {{ orderData.order?.status }}
                </span>
                <span v-if="orderData.order?.payment_status === 'paid'" class="badge bg-success font-mono fs-6 p-2">
                  <i class="fa-solid fa-circle-check me-1"></i> Paid
                </span>
                <span v-else-if="orderData.order?.payment_status === 'payment_on_review'" class="badge bg-info text-white font-mono fs-6 p-2">
                  <i class="fa-solid fa-hourglass-half me-1"></i> On Review
                </span>
                <span v-else class="badge bg-warning text-dark font-mono fs-6 p-2">
                  <i class="fa-solid fa-clock me-1"></i> Unpaid
                </span>
              </div>
              <small class="text-muted d-block font-mono">
                Live Sync Realtime
              </small>
              <div class="mt-2 d-flex align-items-center justify-content-end gap-2 flex-wrap">
                <a :href="'/orders/' + orderData.order?.order_number + '/print'" target="_blank" 
                   class="btn btn-sm btn-outline-dark font-mono d-inline-flex align-items-center gap-1 shadow-sm text-decoration-none"
                   style="font-size: 0.8rem; padding: 4px 10px;">
                  <i class="fa-solid fa-print"></i>
                  <span>Cetak Struk POS</span>
                </a>
                <button v-if="canCancelOrder" @click="openCancelModal" type="button"
                        class="btn btn-sm btn-outline-danger font-mono d-inline-flex align-items-center gap-1 shadow-sm"
                        style="font-size: 0.8rem; padding: 4px 10px;">
                  <i class="fa-solid fa-ban"></i>
                  <span>Batalkan Pesanan</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Pre-Order Batch Info Banner in Header -->
          <div v-if="orderData.queue_info?.is_preorder" class="mt-3 pt-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="badge bg-danger fs-7"><i class="fa-solid fa-bullhorn me-1"></i> BATCH PRE-ORDER</span>
              <strong class="text-dark">{{ orderData.queue_info.campaign_title || orderData.order?.campaign?.title }}</strong>
            </div>
            <div class="badge bg-primary-subtle text-primary border border-primary-subtle font-mono fs-7 p-2">
              <i class="fa-solid fa-calendar-day me-1"></i> Jadwal Siap: <strong>{{ orderData.queue_info.ready_full_formatted }}</strong>
            </div>
          </div>
        </div>

        <!-- QUEUE POSITION & LIVE COOKING STATUS CARD -->
        <div v-if="orderData.queue_info && !orderData.is_rejected" class="card p-4 mb-4 border-2 shadow-sm" 
             :class="orderData.queue_info.is_preorder ? (orderData.order?.status === 'Order Ready' ? 'border-success bg-success-subtle' : 'border-danger bg-danger-subtle') : (orderData.queue_info.is_in_queue ? 'border-primary bg-primary-subtle' : 'border-success bg-success-subtle')">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-circle p-3 d-flex align-items-center justify-content-center text-white" 
                   :class="orderData.queue_info.is_preorder ? (orderData.order?.status === 'Order Ready' ? 'bg-success' : 'bg-danger') : (orderData.queue_info.is_in_queue ? 'bg-primary' : 'bg-success')"
                   style="width: 58px; height: 58px;">
                <i v-if="orderData.queue_info.is_preorder" class="fa-solid fa-calendar-check fs-3"></i>
                <i v-else-if="orderData.queue_info.is_in_queue" class="fa-solid fa-fire fs-3"></i>
                <i v-else-if="orderData.order?.status === 'Order Ready'" class="fa-solid fa-circle-check fs-3"></i>
                <i v-else class="fa-solid fa-motorcycle fs-3"></i>
              </div>
              <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <!-- Pre-Order Badges -->
                  <template v-if="orderData.queue_info.is_preorder">
                    <span class="badge bg-danger fs-6 font-mono px-3 py-1">
                      <i class="fa-solid fa-calendar-star me-1"></i> BATCH PRE-ORDER
                    </span>
                    <span class="badge bg-dark font-mono px-3 py-1">
                      <i class="fa-solid fa-clock me-1"></i> Ready: {{ orderData.queue_info.ready_time_formatted }}
                    </span>
                  </template>
                  <!-- Regular On-Demand Queue Badges -->
                  <template v-else>
                    <span v-if="orderData.queue_info.is_in_queue" class="badge bg-danger fs-6 font-mono px-3 py-1">
                      <i class="fa-solid fa-fire me-1"></i> Nomor Antrean #{{ orderData.queue_info.queue_number }}
                    </span>
                    <span v-else class="badge bg-success fs-6 font-mono px-3 py-1">
                      {{ orderData.queue_info.queue_badge }}
                    </span>
                    
                    <span v-if="orderData.queue_info.is_in_queue && orderData.queue_info.orders_ahead === 0" class="badge bg-primary text-white">
                      Sedang Dimasak Sekarang
                    </span>
                    <span v-else-if="orderData.queue_info.is_in_queue" class="badge bg-warning text-dark font-mono">
                      {{ orderData.queue_info.orders_ahead }} pesanan di depan Anda
                    </span>
                  </template>
                </div>
                <h4 class="fw-bold mb-1 mt-2 text-dark fs-5">
                  {{ orderData.queue_info.queue_message }}
                </h4>
                <small class="text-muted d-block font-mono">
                  Dapur Cabang: <strong>{{ orderData.order?.store?.name }}</strong>
                  <span v-if="!orderData.queue_info.is_preorder"> &bull; Total antrean aktif: {{ orderData.queue_info.total_active_queue }} pesanan</span>
                </small>
              </div>
            </div>

            <!-- Estimated Time Box -->
            <!-- 1. For Pre-Order: Display PO Ready Date & Time -->
            <div v-if="orderData.queue_info.is_preorder && orderData.order?.status !== 'Order Ready'" 
                 class="text-md-end text-start p-3 bg-white rounded border border-danger shadow-xs" style="min-width: 220px;">
              <small class="text-danger d-block text-uppercase fw-bold" style="font-size: 0.75rem;">
                <i class="fa-solid fa-calendar-day me-1"></i> Tanggal Ready Menu PO
              </small>
              <div class="font-mono fw-bold fs-5 text-dark mt-1">
                {{ orderData.queue_info.ready_date_formatted }}
              </div>
              <small class="text-primary fw-semibold d-block font-mono" style="font-size: 0.82rem;">
                <i class="fa-solid fa-clock me-1"></i> Pukul {{ orderData.queue_info.ready_time_formatted }}
              </small>
              <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Jadwal Pengambilan / Delivery</small>
            </div>
            <!-- 2. For Regular Queue -->
            <div v-else-if="!orderData.queue_info.is_preorder && orderData.queue_info.is_in_queue && orderData.queue_info.estimated_minutes" 
                 class="text-md-end text-start p-3 bg-white rounded border shadow-xs" style="min-width: 170px;">
              <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Estimasi Penyajian</small>
              <div class="font-mono fw-bold fs-4 text-primary">
                ~{{ orderData.queue_info.estimated_minutes }} <span class="fs-6 text-muted">Menit</span>
              </div>
              <small class="text-muted" style="font-size: 0.75rem;">Realtime KDS Monitor</small>
            </div>
            <!-- 3. When Order Ready -->
            <div v-else-if="orderData.order?.status === 'Order Ready'" class="p-3 bg-white rounded border border-success text-center shadow-xs">
              <span class="badge bg-success fs-7 mb-1">SIAP DISANTAP</span>
              <div class="fw-bold text-success">Silakan diambil / siap dinikmati!</div>
            </div>
          </div>
        </div>

        <!-- REJECTED / CANCELLED STATE -->
        <div v-if="orderData.is_rejected" class="card alert-reject p-4 mb-4">
          <div class="d-flex align-items-start gap-3">
            <i class="fa-solid fa-circle-xmark text-danger fs-1 flex-shrink-0"></i>
            <div class="flex-grow-1">
              <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <h4 class="fw-bold text-danger mb-0">Pesanan Dibatalkan</h4>
                <span class="badge" :class="orderData.order?.cancelled_by === 'customer' ? 'bg-primary' : 'bg-dark'">
                  Dibatalkan oleh {{ orderData.order?.cancelled_by === 'customer' ? 'Pelanggan' : 'Dapur Cabang' }}
                </span>
              </div>
              <p class="mb-2 text-dark small">
                Pesanan ini telah resmi dibatalkan dan tidak akan diproses oleh dapur.
              </p>
              <div class="p-3 bg-white rounded border border-danger-subtle mb-3">
                <strong class="text-dark small d-block">Alasan Pembatalan:</strong>
                <div class="text-danger fw-bold mt-1">
                  {{ orderData.cancellation_reason || orderData.order?.cancellation_reason || orderData.rejection_reason || 'Pesanan dibatalkan' }}
                </div>
                <div v-if="orderData.cancellation_remark || orderData.order?.cancellation_remark" class="text-muted small mt-1">
                  Catatan: {{ orderData.cancellation_remark || orderData.order?.cancellation_remark }}
                </div>
              </div>

              <!-- Refund Info Banner if exists -->
              <div v-if="orderData.refund_bank_name || orderData.order?.refund_bank_name" class="p-3 bg-white rounded border border-warning shadow-xs mb-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="fa-solid fa-money-bill-transfer text-warning fs-5"></i>
                  <strong class="text-dark small">Pengajuan Pengembalian Dana (Refund H+1)</strong>
                  <span class="badge bg-warning text-dark font-mono ms-auto">{{ (orderData.refund_status || orderData.order?.refund_status || 'PENDING').toUpperCase() }}</span>
                </div>
                <p class="text-muted small mb-2" style="line-height: 1.45;">
                  Pengembalian dana sebesar <strong class="text-success font-mono">Rp {{ formatNumber(orderData.order?.total_amount) }}</strong> 
                  sedang diproses pada <strong>H+1 hari kerja</strong> ke rekening bank berikut:
                </p>
                <div class="p-2 bg-light rounded font-mono small">
                  <div><strong>Bank:</strong> {{ orderData.refund_bank_name || orderData.order?.refund_bank_name }}</div>
                  <div><strong>No. Rekening:</strong> {{ orderData.refund_bank_account_number || orderData.order?.refund_bank_account_number }}</div>
                  <div><strong>Atas Nama:</strong> {{ orderData.refund_bank_account_name || orderData.order?.refund_bank_account_name }}</div>
                </div>
                <small class="text-muted d-block mt-2" style="font-size: 0.75rem;">
                  <i class="fa-solid fa-circle-info text-primary me-1"></i> Bukti pengembalian dana akan dikirimkan ke email {{ orderData.order?.refund_email || orderData.order?.client?.email }}.
                </small>
              </div>

              <a v-if="orderData.order?.store?.phone" 
                 :href="'https://wa.me/' + cleanPhone(orderData.order.store.phone)" target="_blank" class="btn-wa-sm">
                <i class="fa-brands fa-whatsapp text-success me-1"></i> Hubungi Dapur Toko
              </a>
            </div>
          </div>
        </div>

        <!-- 5-STAGE KDS PIPELINE TIMELINE -->
        <div v-else class="card p-4 mb-4">
          <h4 class="fw-bold fs-6 mb-4">
            <i class="fa-solid fa-timeline text-primary me-2"></i> Tahapan Pemrosesan Dapur
          </h4>

          <div class="pipeline-container">
            <div v-for="(stage, idx) in stagesList" :key="stage.name" 
                 :class="['pipeline-step', getStepStatus(idx)]">
              
              <div class="step-icon-wrapper">
                <div class="step-icon">
                  <i :class="stage.icon"></i>
                </div>
                <div v-if="idx < stagesList.length - 1" class="step-line"></div>
              </div>

              <div class="step-details">
                <div class="d-flex justify-content-between align-items-center">
                  <h5 class="step-title mb-0">{{ stage.name }}</h5>
                  <span v-if="idx === orderData.current_stage_index" class="badge badge-primary">
                    SEDANG BERJALAN
                  </span>
                  <span v-else-if="idx < orderData.current_stage_index" class="badge badge-green">
                    SELESAI
                  </span>
                </div>
                <p class="step-desc">{{ stage.description }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ORDER ITEMS BREAKDOWN -->
        <div class="card p-4 mb-4">
          <h4 class="fw-bold fs-6 mb-3">
            <i class="fa-solid fa-utensils text-warning me-2"></i> Rincian Menu yang Sedang Dimasak
          </h4>

          <div class="items-list">
            <div v-for="item in orderData.order?.items" :key="item.id" class="item-row">
              <div class="d-flex align-items-start gap-3">
                <span class="item-qty-badge font-mono">{{ item.quantity }}x</span>
                <div>
                  <h5 class="fw-bold fs-6 mb-1">{{ item.menu_name }}</h5>
                  <div v-if="item.selected_options?.length" class="d-flex flex-wrap gap-1 mb-1">
                    <span v-for="(opt, oIdx) in item.selected_options" :key="oIdx" class="badge bg-light border text-dark">
                      + {{ opt.name }}
                    </span>
                  </div>
                  <small v-if="item.special_notes" class="text-amber d-block">
                    <i class="fa-solid fa-note-sticky me-1"></i> Catatan: {{ item.special_notes }}
                  </small>
                </div>
              </div>

              <div class="fw-bold text-dark font-mono">
                Rp {{ formatNumber(item.subtotal) }}
              </div>
            </div>
          </div>

          <div class="totals-box mt-3 pt-3 border-top">
            <div class="d-flex justify-content-between mb-1 small text-muted">
              <span>Subtotal:</span>
              <span>Rp {{ formatNumber(orderData.order?.subtotal) }}</span>
            </div>
            <div v-if="orderData.order?.discount_amount > 0" class="d-flex justify-content-between mb-1 small text-danger">
              <span>Diskon Promo:</span>
              <span>- Rp {{ formatNumber(orderData.order?.discount_amount) }}</span>
            </div>
            <div v-if="orderData.order?.fulfillment_type === 'delivery' || Number(orderData.order?.shipping_cost) > 0" class="d-flex justify-content-between mb-1 small text-dark">
              <span>
                Ongkos Kirim ({{ String(orderData.order?.shipping_courier || 'KiriminAja').toUpperCase() }}
                <template v-if="orderData.order?.shipping_service_name"> - {{ orderData.order.shipping_service_name }}</template>):
              </span>
              <span class="font-mono">Rp {{ formatNumber(orderData.order?.shipping_cost) }}</span>
            </div>
            <div class="d-flex justify-content-between fs-5 fw-bold text-dark pt-2 border-top">
              <span>Total Pembayaran:</span>
              <span class="text-primary font-mono">Rp {{ formatNumber(orderData.order?.total_amount) }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
              <span class="text-muted small">Status Pembayaran:</span>
              <span v-if="orderData.order?.payment_status === 'paid'" class="badge bg-success font-mono fs-6 px-3 py-1">
                <i class="fa-solid fa-circle-check me-1"></i> Paid
              </span>
              <span v-else-if="orderData.order?.payment_status === 'payment_on_review'" class="badge bg-info text-white font-mono fs-6 px-3 py-1">
                <i class="fa-solid fa-hourglass-half me-1"></i> On Review
              </span>
              <span v-else-if="isCodOrder(orderData.order)" class="badge bg-success text-white font-mono fs-6 px-3 py-1">
                <i class="fa-solid fa-hand-holding-dollar me-1"></i> Bayar di Tempat (COD)
              </span>
              <span v-else class="badge bg-warning text-dark font-mono fs-6 px-3 py-1">
                <i class="fa-solid fa-clock me-1"></i> Unpaid
              </span>
            </div>

            <div class="mt-3 pt-2 border-top text-end">
              <a :href="'/orders/' + orderData.order?.order_number + '/print'" target="_blank" 
                 class="btn btn-sm btn-outline-dark font-mono d-inline-flex align-items-center gap-1 shadow-sm text-decoration-none">
                <i class="fa-solid fa-print"></i>
                <span>Cetak Struk Resmi POS</span>
              </a>
            </div>
          </div>

          <!-- Delivery / Shipping Info (Kirim Langsung ke Rumah) -->
          <div v-if="orderData.order?.fulfillment_type === 'delivery'" class="mt-3 p-3 rounded-3 border border-info-subtle bg-light">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
              <span class="fw-bold text-dark small">
                <i class="fa-solid fa-motorcycle text-primary me-1"></i>
                Pengiriman KiriminAja ({{ String(orderData.order?.shipping_courier || '-').toUpperCase() }} — {{ orderData.order?.shipping_service_name || '-' }})
              </span>
              <span class="badge bg-info text-dark font-mono">
                {{ String(orderData.order?.shipping_status || 'waiting_shipment').toUpperCase() }}
              </span>
            </div>
            <div v-if="orderData.order?.ka_awb" class="small mb-1">
              <span class="text-muted">No. Resi (AWB):</span>
              <strong class="font-mono text-primary ms-1">{{ orderData.order.ka_awb }}</strong>
            </div>
            <div v-if="orderData.order?.shipping_etd" class="small mb-1 text-muted">
              Estimasi Pengiriman: <strong class="text-dark">{{ orderData.order.shipping_etd }}</strong>
            </div>
            <div v-if="orderData.order?.shipping_address" class="small mt-2 pt-2 border-top">
              <strong class="d-block text-dark">Alamat Tujuan:</strong>
              <span class="text-dark">
                {{ orderData.order.shipping_address.address }},
                <template v-if="orderData.order.shipping_address.subdistrict_name">Kel. {{ orderData.order.shipping_address.subdistrict_name }}, </template>
                <template v-if="orderData.order.shipping_address.district_name">Kec. {{ orderData.order.shipping_address.district_name }}, </template>
                {{ orderData.order.shipping_address.city_name || '' }} {{ orderData.order.shipping_address.postal_code || '' }}
              </span>
              <div v-if="orderData.order.shipping_address.address_note" class="text-primary mt-1">
                <i class="fa-solid fa-signs-post me-1"></i> Patokan / Gang: {{ orderData.order.shipping_address.address_note }}
              </div>
              <div v-if="orderData.order.shipping_address.latitude && orderData.order.shipping_address.longitude" class="mt-1">
                <a :href="'https://www.openstreetmap.org/?mlat=' + orderData.order.shipping_address.latitude + '&mlon=' + orderData.order.shipping_address.longitude + '#map=17/' + orderData.order.shipping_address.latitude + '/' + orderData.order.shipping_address.longitude"
                   target="_blank" rel="noopener" class="text-decoration-none fw-semibold">
                  <i class="fa-solid fa-map-pin text-danger me-1"></i> Lihat Pin Lokasi (OpenStreetMap)
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Reassurance Card: Info Cashback Poin Pelanggan Setia -->
        <div v-if="orderData.queue_info?.is_preorder && orderData.order?.campaign?.discount_type === 'min_qty' && (!orderData.order?.discount_amount || orderData.order?.discount_amount == 0)" 
             class="card p-4 mb-4 border-primary-subtle bg-primary-subtle shadow-sm">
          <div class="d-flex align-items-start gap-3">
            <div class="rounded-circle bg-warning text-dark p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
              <i class="fa-solid fa-coins fs-4"></i>
            </div>
            <div class="flex-grow-1">
              <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <span class="badge bg-primary text-white font-mono">
                  <i class="fa-solid fa-crown text-warning me-1"></i> Info Promo & Cashback Poin
                </span>
                <span class="badge bg-warning text-dark font-mono">
                  Target Promo: Min. {{ orderData.order?.campaign?.min_order_qty || 15 }} Porsi
                </span>
              </div>
              <h5 class="fw-bold mb-1 text-primary fs-6">
                Sudah Bayar Tapi Kuota Promo Belum Tercapai?
              </h5>
              <p class="small text-dark mb-2" style="line-height: 1.5;">
                <strong>Tenang saja!</strong> Jika batch Open PO ini nantinya melebihi dari batas minimum ({{ orderData.order?.campaign?.min_order_qty || 15 }} porsi) dari pesanan pembeli lain dan kamu mendaftar / terdaftar sebagai <strong>Pelanggan Setia</strong>, selisih cashback promo bisa ditukar dengan <strong>Point</strong>, dan point bisa dipakai untuk mengurangi total bayar menu di pesanan selanjutnya!
              </p>
              <div v-if="!orderData.order?.client?.is_loyalty_member" class="p-3 rounded bg-white border border-primary-subtle d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
                <div class="small text-muted">
                  <i class="fa-solid fa-circle-info text-primary me-1"></i> Anda saat ini terdaftar sebagai Tamu. Yuk daftarkan akun Pelanggan Setia agar cashback poin promo tersimpan otomatis.
                </div>
                <Link href="/" class="btn btn-sm btn-primary fw-bold text-nowrap">
                  <i class="fa-solid fa-user-plus me-1"></i> Daftar Pelanggan Setia
                </Link>
              </div>
              <div v-else class="small text-success fw-bold mt-2">
                <i class="fa-solid fa-circle-check me-1"></i> Akun Anda telah terdaftar sebagai Pelanggan Setia ({{ orderData.order?.client?.email }}). Saldo poin akan otomatis masuk jika syarat terpenuhi.
              </div>
            </div>
          </div>
        </div>

        <!-- PAYMENT STATUS & ONLINE PAYMENT / QRIS SECTION -->
        <!-- 1. If Paid -->
        <div v-if="orderData.order?.payment_status === 'paid'" class="card p-4 mb-4 border-success bg-success-subtle shadow-sm">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-circle bg-success text-white p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                <i class="fa-solid fa-check fs-5"></i>
              </div>
              <div>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-success text-white fs-6 font-mono px-3 py-1">Paid</span>
                  <h5 class="fw-bold mb-0 text-success">Pembayaran Lunas</h5>
                </div>
                <small class="text-muted d-block mt-1">Metode: {{ orderData.order?.payment_method }} • Pembayaran terkonfirmasi.</small>
              </div>
            </div>
            <div v-if="orderData.order?.payment_proof_url" class="text-end">
              <a :href="orderData.order.payment_proof_url" target="_blank" class="btn btn-outline-success btn-sm fw-semibold d-flex align-items-center gap-2">
                <i class="fa-solid fa-file-image"></i>
                <span>Lihat Bukti QRIS</span>
              </a>
            </div>
          </div>
        </div>

        <!-- 2. If Payment On Review (Bukti telah diunggah dan sedang dicek admin) -->
        <div v-else-if="orderData.order?.payment_status === 'payment_on_review'" class="card p-4 mb-4 border-info bg-info-subtle shadow-sm">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-circle bg-info text-white p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                <i class="fa-solid fa-hourglass-half fs-5"></i>
              </div>
              <div>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-info text-white fs-6 font-mono px-3 py-1">Payment on Review</span>
                  <h5 class="fw-bold mb-0 text-primary">Bukti Pembayaran Sedang Diverifikasi</h5>
                </div>
                <small class="text-muted d-block mt-1">Bukti transfer QRIS telah kami terima dan sedang diverifikasi oleh admin / kasir cabang. Status pesanan akan otomatis berganti menjadi <strong>Paid</strong> setelah disetujui.</small>
              </div>
            </div>
            <div v-if="orderData.order?.payment_proof_url" class="text-end">
              <a :href="orderData.order.payment_proof_url" target="_blank" class="btn btn-outline-primary btn-sm fw-semibold d-flex align-items-center gap-2">
                <i class="fa-solid fa-file-image"></i>
                <span>Lihat Bukti Terunggah</span>
              </a>
            </div>
          </div>
        </div>

        <!-- 3. If Unpaid with Payment Gateway Link -->
        <div v-else-if="orderData.order?.payment_url || orderData.order?.snap_token" class="card p-4 mb-4 border-primary shadow-sm">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h4 class="fw-bold fs-6 mb-0 text-dark">
              <i class="fa-solid fa-credit-card text-success me-2"></i>
              {{ (orderData.order?.payment_gateway_provider === 'midtrans' || orderData.order?.snap_token) ? 'Pembayaran Online Midtrans Snap' : 'Pembayaran Online Mayar.id' }}
            </h4>
            <span class="badge bg-warning text-dark">Menunggu Pembayaran</span>
          </div>
          <p class="text-muted small mb-3">Pesanan Anda memiliki tagihan online resmi. Klik tombol di bawah untuk bayar dengan QRIS, Virtual Account, atau E-Wallet:</p>
          <a :href="orderData.order.payment_url" target="_blank" class="btn btn-danger py-2 px-4 fw-bold text-white d-flex align-items-center justify-content-center gap-2 text-decoration-none shadow-sm">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
            <span>Bayar Sekarang via {{ (orderData.order?.payment_gateway_provider === 'midtrans' || orderData.order?.snap_token) ? 'Midtrans' : 'Mayar.id' }} (Rp {{ formatNumber(orderData.order?.total_amount) }})</span>
          </a>
          <small class="text-muted d-block text-center mt-2">Status pesanan akan otomatis terverifikasi setelah Anda menyelesaikan pembayaran.</small>
        </div>

        <!-- 4. If QRIS (Statik / Dinamis) -->
        <div v-else-if="orderData.order?.payment_method === 'QRIS Dinamis' || orderData.order?.payment_method === 'QRIS Statik'" class="card p-4 mb-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="fw-bold fs-6 mb-0">
              <i class="fa-solid fa-qrcode text-primary me-2"></i> Pembayaran QRIS Toko
            </h4>
            <span class="badge bg-warning text-dark font-mono"><i class="fa-solid fa-clock me-1"></i> Menunggu Bukti QRIS</span>
          </div>

          <div class="text-center p-3 bg-light rounded border mb-4">
            <p class="text-muted small mb-2">Scan QRIS berikut untuk menyelesaikan pembayaran pesanan Anda:</p>
            <div class="qris-box-wrapper d-inline-block p-2 bg-white rounded border shadow-sm">
              <img :src="orderData.order?.store?.qris_image || 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=NAUMIL_RICE'" 
                   alt="QRIS Toko" class="qris-img" />
            </div>
            <div class="mt-2">
              <button type="button" class="btn btn-outline-primary btn-sm fw-bold shadow-sm" @click="downloadQrisImage">
                <i class="fa-solid fa-download me-1"></i> Unduh / Simpan Gambar QRIS
              </button>
            </div>
            <div class="mt-2 font-mono fw-bold text-primary fs-5">
              Rp {{ formatNumber(orderData.order?.total_amount) }}
            </div>
          </div>

          <!-- Upload Bukti QRIS Form -->
          <div class="upload-proof-box p-3 border rounded bg-white shadow-sm">
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="fa-solid fa-file-arrow-up text-primary fs-5"></i>
              <h5 class="fw-bold fs-6 mb-0 text-dark">Upload Bukti Pembayaran QRIS</h5>
            </div>
            <p class="text-muted small mb-3">
              Setelah melakukan transfer/scan QRIS, silakan upload bukti pembayaran (screenshot/foto struk) di bawah ini. Status pembayaran Anda akan ditinjau kasir/admin sebelum berubah menjadi <strong>Paid</strong>.
            </p>

            <div v-if="uploadSuccess" class="alert alert-info d-flex align-items-center gap-2 p-2 mb-3">
              <i class="fa-solid fa-hourglass-half fs-5 text-info"></i>
              <div>
                <strong>Berhasil Terkirim!</strong> Bukti QRIS berhasil diunggah. Status pesanan Anda kini <strong>Payment on Review</strong> dan sedang diverifikasi oleh admin.
              </div>
            </div>

            <div v-if="uploadError" class="alert alert-danger d-flex align-items-center gap-2 p-2 mb-3">
              <i class="fa-solid fa-circle-exclamation fs-5"></i>
              <div>{{ uploadError }}</div>
            </div>

            <!-- Upload Input Area -->
            <div v-if="!proofPreview" class="upload-dropzone p-4 text-center rounded bg-light mb-3" @click="triggerFileInput" style="cursor: pointer; border: 2px dashed #cbd5e1;">
              <input ref="fileInputRef" type="file" accept="image/jpeg,image/png,image/jpg,image/webp" class="d-none" @change="handleFileChange" />
              <i class="fa-solid fa-cloud-arrow-up fs-1 text-primary mb-2"></i>
              <div class="fw-bold text-dark mb-1">Klik untuk Memilih Foto Bukti QRIS</div>
              <small class="text-muted d-block">Format: JPG, PNG, WEBP (Maksimal 5MB)</small>
            </div>

            <!-- Preview Selected File -->
            <div v-else class="preview-box p-3 border rounded bg-light mb-3">
              <div class="d-flex align-items-center gap-3">
                <img :src="proofPreview" alt="Pratinjau Bukti" class="rounded border" style="width: 80px; height: 80px; object-fit: cover;" />
                <div class="flex-grow-1">
                  <div class="fw-bold text-dark text-truncate" style="max-width: 260px;">{{ selectedFile?.name }}</div>
                  <small class="text-muted d-block">{{ formatFileSize(selectedFile?.size) }}</small>
                  <span class="badge bg-success-subtle text-success border border-success-subtle mt-1">Siap Diunggah</span>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm" @click="clearSelectedFile" :disabled="uploading">
                  <i class="fa-solid fa-trash me-1"></i> Ganti
                </button>
              </div>
            </div>

            <button type="button" class="btn btn-primary w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm" 
                    :disabled="!selectedFile || uploading" @click="uploadProof">
              <span v-if="uploading">
                <i class="fa-solid fa-spinner fa-spin me-1"></i> Mengunggah Bukti Pembayaran...
              </span>
              <span v-else>
                <i class="fa-solid fa-paper-plane me-1"></i> Unggah Bukti & Kirim ke Admin
              </span>
            </button>
          </div>
        </div>

        <!-- 5. If COD (Bayar di Tempat) -->
        <div v-else-if="isCodOrder(orderData.order)" class="card p-4 mb-4 border-success-subtle bg-light shadow-sm">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-success text-white p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
              <i class="fa-solid fa-hand-holding-dollar fs-5"></i>
            </div>
            <div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success text-white font-mono">COD</span>
                <h5 class="fw-bold mb-0 text-dark">Bayar Tunai di Tempat</h5>
              </div>
              <small class="text-muted d-block mt-1">Siapkan uang tunai sebesar <strong>Rp {{ formatNumber(orderData.order?.total_amount) }}</strong> saat pesanan tiba atau diambil.</small>
            </div>
          </div>
        </div>

        <!-- Fallback if Mayar Payment Gateway chosen but payment_url is not yet generated -->
        <div v-else-if="orderData.order?.payment_status === 'unpaid' && (orderData.order?.payment_method === 'Payment Gateway' || orderData.order?.payment_method === 'Payment Gateway (Mayar.id)')" class="card p-4 mb-4 border-warning shadow-sm">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="fa-solid fa-triangle-exclamation text-warning fs-5"></i>
            <h4 class="fw-bold fs-6 mb-0 text-dark">Invoice Online Belum Tersedia</h4>
          </div>
          <p class="text-muted small mb-3">Invoice online Mayar belum dapat dibuat secara otomatis (periksa izin WRITE API Key di Mayar Dashboard). Anda dapat menyelesaikan pembayaran langsung melalui WhatsApp dapur toko.</p>
          <a v-if="orderData.order?.store?.phone" 
             :href="'https://wa.me/' + cleanPhone(orderData.order.store.phone) + '?text=Halo%20' + (orderData.order?.store?.name || 'Toko') + ',%20saya%20ingin%20membayar%20pesanan%20' + orderData.order?.order_number" 
             target="_blank" class="btn btn-outline-success py-2 px-3 fw-bold d-flex align-items-center justify-content-center gap-2 text-decoration-none">
            <i class="fa-brands fa-whatsapp fs-5"></i>
            <span>Minta Bantuan Pembayaran via WhatsApp</span>
          </a>
        </div>

        <!-- STORE CONTACT & SUPPORT -->
        <div class="card p-4 text-center">
          <h5 class="fw-bold mb-1">Ada Pertanyaan Mengenai Pesanan Anda?</h5>
          <p class="text-muted small mb-3">Tim dapur dan kurir cabang siap membantu Anda.</p>
          <a v-if="orderData.order?.store?.phone" 
             :href="'https://wa.me/' + cleanPhone(orderData.order.store.phone) + '?text=Halo%20Naumil%20Rice,%20saya%20ingin%20menanyakan%20status%20pesanan%20' + orderData.order.order_number" 
             target="_blank" class="btn btn-primary mx-auto">
            <i class="fa-brands fa-whatsapp fs-5"></i> Hubungi WhatsApp Dapur
          </a>
        </div>
      </div>
    </div>

    <!-- CANCEL ORDER POPUP MODAL -->
    <div v-if="showCancelModal" class="cancel-modal-backdrop" @click.self="closeCancelModal">
      <div class="cancel-modal-dialog animate-scale-in">
        <div class="cancel-modal-content">
          <!-- Modal Header -->
          <div class="cancel-modal-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <div class="cancel-icon-circle">
                <i class="fa-solid fa-ban text-danger"></i>
              </div>
              <div>
                <h5 class="fw-bold mb-0 text-dark">Batalkan Pesanan</h5>
                <small class="text-muted font-mono">{{ orderData.order?.order_number }}</small>
              </div>
            </div>
            <button type="button" class="btn-close-modal" @click="closeCancelModal" title="Tutup">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- Modal Body -->
          <div class="cancel-modal-body p-4">
            <div v-if="cancelError" class="alert alert-danger py-2 px-3 small d-flex align-items-center gap-2 mb-3">
              <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
              <div>{{ cancelError }}</div>
            </div>

            <p class="text-muted small mb-3">
              Mohon beritahu kami alasan pembatalan pesanan Anda agar kami dapat terus meningkatkan kualitas layanan Naumil Rice.
            </p>

            <!-- 1. Alasan Pembatalan (Dropdown dari Master Backend) -->
            <div class="mb-3">
              <label class="form-label fw-bold text-dark small">Pilih Alasan Pembatalan <span class="text-danger">*</span></label>
              <select v-model="cancelForm.reason_id" class="form-select form-select-custom" @change="onReasonChange" :disabled="cancelSubmitting">
                <option value="">-- Pilih Alasan Pembatalan --</option>
                <option v-for="r in cancellationReasons" :key="r.id" :value="r.id">
                  {{ r.reason }}
                </option>
              </select>
            </div>

            <!-- Jika 'Lainnya' dipilih -> Remark Wajib Diisi -->
            <div v-if="isRemarkRequired" class="mb-3 animate-fade-in">
              <label class="form-label fw-bold text-danger small">
                Tuliskan Alasan / Catatan Pembatalan <span class="text-danger">*</span>
              </label>
              <textarea v-model="cancelForm.remark" rows="3" class="form-control form-control-custom" 
                        placeholder="Contoh: Ada keperluan mendadak, mohon dibatalkan..." 
                        :disabled="cancelSubmitting" required></textarea>
              <small class="text-muted" style="font-size: 0.75rem;">* Wajib diisi karena Anda memilih opsi Lainnya.</small>
            </div>

            <!-- 2. Informasi Pengembalian Dana & OTP (HANYA JIKA BAYAR LANGSUNG / MIDTRANS) -->
            <div v-if="isMidtransOrPaidOrder" class="refund-section mt-3 pt-3 border-top animate-fade-in">
              <!-- Info Box H+1 -->
              <div class="refund-notice-box mb-3 p-3 rounded border border-warning bg-warning-subtle">
                <div class="d-flex align-items-start gap-2">
                  <i class="fa-solid fa-shield-halved text-warning-emphasis fs-5 mt-1 flex-shrink-0"></i>
                  <div>
                    <strong class="text-dark d-block">💡 Informasi Pengembalian Dana (Refund H+1)</strong>
                    <p class="mb-0 text-dark small" style="line-height: 1.45;">
                      Untuk pesanan yang dibayar langsung menggunakan Midtrans/online, pengembalian dana sebesar 
                      <strong class="text-success font-mono">Rp {{ formatNumber(orderData.order?.total_amount) }}</strong> 
                      akan diproses pada <strong>H+1 hari kerja</strong>. Pastikan email aktif & nomor rekening Anda sudah benar untuk menerima notifikasi status pengembalian.
                    </p>
                  </div>
                </div>
              </div>

              <!-- Form Rekening Bank -->
              <div class="row g-2 mb-3">
                <div class="col-12">
                  <label class="form-label fw-bold text-dark small">Email Aktif untuk Notifikasi Refund <span class="text-danger">*</span></label>
                  <input type="email" v-model="cancelForm.refund_email" class="form-control form-control-custom" 
                         placeholder="email@anda.com" :disabled="cancelSubmitting || otpSent" required />
                  <small class="text-muted" style="font-size: 0.74rem;">Kode OTP dan bukti transfer refund akan dikirimkan ke email ini.</small>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold text-dark small">Nama Bank <span class="text-danger">*</span></label>
                  <select v-model="cancelForm.refund_bank_name" class="form-select form-select-custom" :disabled="cancelSubmitting || otpSent">
                    <option value="BCA">Bank BCA</option>
                    <option value="Mandiri">Bank Mandiri</option>
                    <option value="BRI">Bank BRI</option>
                    <option value="BNI">Bank BNI</option>
                    <option value="BSI">Bank Syariah Indonesia (BSI)</option>
                    <option value="CIMB Niaga">Bank CIMB Niaga</option>
                    <option value="Permata">Bank Permata</option>
                    <option value="Danamon">Bank Danamon</option>
                    <option value="BTN">Bank BTN</option>
                    <option value="Jenius">Bank BTPN / Jenius</option>
                    <option value="SeaBank">SeaBank</option>
                    <option value="Lainnya">Bank Lainnya</option>
                  </select>
                </div>

                <div v-if="cancelForm.refund_bank_name === 'Lainnya'" class="col-md-6 animate-fade-in">
                  <label class="form-label fw-bold text-dark small">Sebutkan Nama Bank <span class="text-danger">*</span></label>
                  <input type="text" v-model="cancelForm.custom_bank_name" class="form-control form-control-custom" 
                         placeholder="Contoh: Bank Neo Commerce, dll" :disabled="cancelSubmitting || otpSent" required />
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold text-dark small">Nomor Rekening Bank <span class="text-danger">*</span></label>
                  <input type="text" v-model="cancelForm.refund_bank_account_number" class="form-control form-control-custom font-mono" 
                         placeholder="Contoh: 1234567890" :disabled="cancelSubmitting || otpSent" required />
                </div>

                <div class="col-12">
                  <label class="form-label fw-bold text-dark small">Nama Pemilik Rekening (Nama Akun) <span class="text-danger">*</span></label>
                  <input type="text" v-model="cancelForm.refund_bank_account_name" class="form-control form-control-custom" 
                         placeholder="Contoh: Sesuai buku tabungan / m-banking" :disabled="cancelSubmitting || otpSent" required />
                </div>
              </div>

              <!-- OTP Verification Box -->
              <div class="otp-box p-3 rounded border bg-light">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <label class="form-label fw-bold text-dark mb-0 small">
                    <i class="fa-solid fa-key text-warning me-1"></i> Verifikasi OTP dari Email <span class="text-danger">*</span>
                  </label>
                  <span v-if="otpSent" class="badge bg-success-subtle text-success border border-success-subtle font-mono small">
                    OTP Terkirim
                  </span>
                </div>

                <div v-if="!otpSent">
                  <p class="text-muted small mb-2" style="font-size: 0.8rem;">
                    Klik tombol di bawah untuk mengirimkan 6 digit kode OTP ke email aktif Anda:
                  </p>
                  <button type="button" class="btn btn-primary btn-sm fw-bold w-100 py-2 shadow-xs" 
                          :disabled="sendingOtp || !isRefundBankFormValid" @click="sendCancelOtp">
                    <span v-if="sendingOtp"><i class="fa-solid fa-spinner fa-spin me-1"></i> Mengirim OTP...</span>
                    <span v-else><i class="fa-solid fa-paper-plane me-1"></i> Kirim Kode OTP ke Email</span>
                  </button>
                  <small v-if="!isRefundBankFormValid" class="text-muted d-block text-center mt-1" style="font-size: 0.72rem;">
                    (Lengkapi email dan data rekening di atas terlebih dahulu)
                  </small>
                </div>

                <div v-else class="animate-fade-in">
                  <div class="alert alert-info py-2 px-3 small d-flex align-items-center justify-content-between mb-2">
                    <div>
                      <i class="fa-solid fa-envelope-circle-check text-info me-1"></i>
                      <span>Kode OTP telah dikirim ke <strong>{{ cancelForm.refund_email }}</strong>.</span>
                    </div>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <input type="text" v-model="cancelForm.otp" maxlength="6" 
                           class="form-control form-control-custom font-mono text-center fs-4 fw-bold" 
                           placeholder="------" style="letter-spacing: 6px;" :disabled="cancelSubmitting" />
                    <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap py-2 px-3" 
                            :disabled="sendingOtp || otpCountdown > 0" @click="sendCancelOtp">
                      <span v-if="otpCountdown > 0">{{ otpCountdown }}s</span>
                      <span v-else>Kirim Ulang</span>
                    </button>
                  </div>
                  <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                    Periksa folder Inbox atau Spam email Anda. Masukkan 6 digit kode OTP.
                  </small>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="cancel-modal-footer p-3 bg-light d-flex justify-content-end gap-2 border-top">
            <button type="button" class="btn btn-light fw-semibold" @click="closeCancelModal" :disabled="cancelSubmitting">
              Batal / Tutup
            </button>
            <button type="button" class="btn btn-danger fw-bold px-4 shadow-sm" 
                    :disabled="!isCancelFormValid || cancelSubmitting" @click="submitCancelOrder">
              <span v-if="cancelSubmitting">
                <i class="fa-solid fa-spinner fa-spin me-1"></i> Membatalkan Pesanan...
              </span>
              <span v-else>
                <i class="fa-solid fa-ban me-1"></i> Ya, Batalkan Pesanan
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  orderNumber: {
    type: String,
    default: ''
  }
})

const isCodOrder = (order) => {
  if (!order) return false
  const pm = String(order.payment_method || '').toLowerCase()
  return pm === 'bayar terima makanan' || pm.includes('cod') || pm.includes('tunai') || pm.includes('terima makanan') || pm.includes('cash')
}

const orderSearchInput = ref(props.orderNumber || '')
const loading = ref(false)
const notFound = ref(false)
const orderData = ref(null)

const selectedFile = ref(null)
const proofPreview = ref(null)
const fileInputRef = ref(null)
const uploading = ref(false)
const uploadError = ref('')
const uploadSuccess = ref(false)

const triggerFileInput = () => {
  if (fileInputRef.value) fileInputRef.value.click()
}

const handleFileChange = (e) => {
  uploadError.value = ''
  uploadSuccess.value = false
  const file = e.target.files?.[0]
  if (!file) return

  const allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']
  if (!allowed.includes(file.type)) {
    uploadError.value = 'Format file tidak didukung. Mohon upload foto gambar (JPG, PNG, atau WEBP).'
    return
  }

  if (file.size > 5 * 1024 * 1024) {
    uploadError.value = 'Ukuran file melebihi batas 5MB.'
    return
  }

  selectedFile.value = file
  proofPreview.value = URL.createObjectURL(file)
}

const clearSelectedFile = () => {
  selectedFile.value = null
  if (proofPreview.value) {
    URL.revokeObjectURL(proofPreview.value)
    proofPreview.value = null
  }
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const downloadQrisImage = async () => {
  const url = orderData.value?.order?.store?.qris_image
  if (!url) return
  const storeName = orderData.value?.order?.store?.name || 'Naumil'
  try {
    const response = await fetch(url)
    const blob = await response.blob()
    const blobUrl = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = blobUrl
    a.download = `QRIS-${storeName.replace(/\s+/g, '_')}.png`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(blobUrl)
  } catch (e) {
    const a = document.createElement('a')
    a.href = url
    a.target = '_blank'
    a.download = `QRIS-${storeName.replace(/\s+/g, '_')}.png`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
  }
}

const uploadProof = async () => {
  if (!selectedFile.value || !orderData.value?.order) return
  try {
    uploading.value = true
    uploadError.value = ''
    uploadSuccess.value = false

    const formData = new FormData()
    formData.append('proof_image', selectedFile.value)

    const orderNum = orderData.value.order.order_number
    const res = await axios.post(`/api/v1/orders/${orderNum}/upload-proof`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    if (res.data.success) {
      uploadSuccess.value = true
      orderData.value.order.payment_status = 'payment_on_review'
      orderData.value.order.payment_proof = res.data.data.order?.payment_proof
      orderData.value.order.payment_proof_url = res.data.data.payment_proof_url
      clearSelectedFile()
    } else {
      uploadError.value = res.data.message || 'Gagal mengunggah bukti QRIS.'
    }
  } catch (err) {
    uploadError.value = err.response?.data?.message || 'Terjadi kesalahan saat mengunggah bukti pembayaran.'
  } finally {
    uploading.value = false
  }
}

let pollTimer = null

const stagesList = computed(() => {
  const isPO = orderData.value?.queue_info?.is_preorder
  const readyTxt = orderData.value?.queue_info?.ready_full_formatted
  return [
    {
      name: 'Order Konfirmed',
      icon: 'fa-solid fa-check',
      description: isPO 
        ? `Pesanan Pre-Order diterima & tercatat untuk jadwal ready ${readyTxt || 'batch PO'}.`
        : 'Pesanan telah diterima oleh sistem dan masuk ke antrean dapur.'
    },
    {
      name: 'Order On Proses',
      icon: 'fa-solid fa-fire-burner',
      description: isPO
        ? 'Dapur menyiapkan bahan baku fresh dan mulai memasak batch menu Pre-Order.'
        : 'Dapur sedang memasak nasi dan lauk pesanan Anda secara fresh.'
    },
    {
      name: 'Order On Packaging',
      icon: 'fa-solid fa-box-open',
      description: isPO
        ? 'Menu Pre-Order telah matang dan dikemas rapi dalam food packaging higienis.'
        : 'Makanan telah matang dan sedang dikemas rapat dalam food packaging.'
    },
    {
      name: 'Order On Delivery',
      icon: 'fa-solid fa-motorcycle',
      description: isPO
        ? 'Pesanan batch Pre-Order siap diambil di cabang atau sedang diantar kurir.'
        : 'Pesanan sedang dibawa kurir menuju alamat Anda / siap diambil.'
    },
    {
      name: 'Order Ready',
      icon: 'fa-solid fa-circle-check',
      description: isPO
        ? 'Pesanan Pre-Order telah siap dan dapat dinikmati. Selamat menyantap!'
        : 'Pesanan telah sampai dan siap disantap. Selamat menikmati!'
    }
  ]
})

let pusherInstance = null
let currentSubscribedOrder = null
let globalOrdersChannel = null

const initTraceWebSocket = (orderNum) => {
  if (typeof window === 'undefined' || typeof window.Pusher === 'undefined') {
    return
  }

  try {
    if (!pusherInstance) {
      pusherInstance = new window.Pusher('l0eg4pqm0kfrx3ctzlls', {
        wsHost: window.location.hostname || '127.0.0.1',
        wsPort: 8085,
        wssPort: 8085,
        forceTLS: false,
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        cluster: 'mt1'
      })
    }

    // Subscribe to specific order channel
    if (orderNum && orderNum !== currentSubscribedOrder) {
      if (currentSubscribedOrder) {
        pusherInstance.unsubscribe(`order.${currentSubscribedOrder}`)
      }
      currentSubscribedOrder = orderNum
      const orderChannel = pusherInstance.subscribe(`order.${orderNum}`)
      orderChannel.bind('order.status.updated', () => {
        silentRefresh()
      })
    }

    // Subscribe to global orders channel
    if (!globalOrdersChannel) {
      globalOrdersChannel = pusherInstance.subscribe('orders')
      globalOrdersChannel.bind('order.status.updated', (data) => {
        if (data.order && data.order.order_number === orderSearchInput.value?.trim()) {
          silentRefresh()
        }
      })
    }
  } catch (err) {
    console.warn('Trace WebSocket error, relying on auto-polling fallback', err)
  }
}

const handleSearch = () => {
  const code = orderSearchInput.value.trim()
  if (code) {
    window.history.pushState({}, '', '/trace/' + code)
    loadTrace(code)
  }
}

const loadTrace = async (orderNum) => {
  if (!orderNum || !orderNum.trim()) return
  try {
    loading.value = true
    notFound.value = false
    const res = await axios.get(`/api/v1/orders/trace/${orderNum.trim()}`)
    if (res.data.success) {
      orderData.value = res.data.data
      initTraceWebSocket(orderNum.trim())
    }
  } catch (err) {
    notFound.value = true
    orderData.value = null
  } finally {
    loading.value = false
  }
}

// Background silent refresh for live real-time sync
const silentRefresh = async () => {
  if (!orderSearchInput.value) return
  try {
    const res = await axios.get(`/api/v1/orders/trace/${orderSearchInput.value.trim()}`)
    if (res.data.success) {
      orderData.value = res.data.data
    }
  } catch (err) {
    // Keep current state on error
  }
}

const getStepStatus = (idx) => {
  if (!orderData.value) return ''
  const current = orderData.value.current_stage_index
  if (idx < current) return 'completed'
  if (idx === current) return 'active'
  return 'pending'
}

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'Order Konfirmed': return 'badge-dark'
    case 'Order On Proses': return 'badge-primary'
    case 'Order On Packaging': return 'badge-amber'
    case 'Order On Delivery': return 'badge-primary'
    case 'Order Ready': return 'badge-green'
    case 'Order Reject': return 'badge-red'
    default: return 'badge-dark'
  }
}

const formatNumber = (num) => {
  if (!num) return '0'
  return new Intl.NumberFormat('id-ID').format(num)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }) + ' WIB'
}

const cleanPhone = (phone) => {
  return phone.replace(/[^0-9]/g, '').replace(/^0/, '62')
}

// ==========================================
// CANCEL ORDER & REFUND STATE
// ==========================================
const showCancelModal = ref(false)
const cancellationReasons = ref([])
const loadingReasons = ref(false)
const cancelSubmitting = ref(false)
const cancelError = ref('')
const otpSent = ref(false)
const sendingOtp = ref(false)
const otpCountdown = ref(0)
let otpCountdownTimer = null

const cancelForm = ref({
  reason_id: '',
  reason: '',
  remark: '',
  refund_email: '',
  refund_bank_name: 'BCA',
  custom_bank_name: '',
  refund_bank_account_number: '',
  refund_bank_account_name: '',
  otp: ''
})

const canCancelOrder = computed(() => {
  if (!orderData.value?.order) return false
  const status = orderData.value.order.status
  return status !== 'Order Reject' && status !== 'Order Ready' && status !== 'Order On Delivery' && !orderData.value.order.cancelled_at
})

const isMidtransOrPaidOrder = computed(() => {
  if (!orderData.value?.order) return false
  const ord = orderData.value.order
  if (isCodOrder(ord)) return false

  const pm = String(ord.payment_method || '').toLowerCase()
  const isMidtransMethod = pm.includes('midtrans') || pm.includes('payment gateway') || ord.payment_gateway_provider === 'midtrans' || !!ord.snap_token
  const isPaid = ord.payment_status === 'paid' || ord.payment_status === 'payment_on_review'

  return isMidtransMethod || isPaid
})

const selectedReason = computed(() => {
  return cancellationReasons.value.find(r => r.id === cancelForm.value.reason_id)
})

const isRemarkRequired = computed(() => {
  if (!selectedReason.value) return false
  return selectedReason.value.requires_remark || selectedReason.value.reason?.toLowerCase() === 'lainnya'
})

const isRefundBankFormValid = computed(() => {
  if (!isMidtransOrPaidOrder.value) return true
  const f = cancelForm.value
  const hasEmail = !!f.refund_email && f.refund_email.includes('@')
  const hasBank = f.refund_bank_name === 'Lainnya' ? !!f.custom_bank_name?.trim() : !!f.refund_bank_name
  const hasNorek = !!f.refund_bank_account_number?.trim()
  const hasNama = !!f.refund_bank_account_name?.trim()
  return hasEmail && hasBank && hasNorek && hasNama
})

const isCancelFormValid = computed(() => {
  if (!cancelForm.value.reason_id) return false
  if (isRemarkRequired.value && !cancelForm.value.remark?.trim()) return false

  if (isMidtransOrPaidOrder.value) {
    if (!isRefundBankFormValid.value) return false
    if (!cancelForm.value.otp || cancelForm.value.otp.trim().length !== 6) return false
  }

  return true
})

const fetchCancellationReasons = async () => {
  if (cancellationReasons.value.length > 0) return
  try {
    loadingReasons.value = true
    const res = await axios.get('/api/v1/cancellation-reasons?applies_to=customer')
    if (res.data?.success && Array.isArray(res.data?.data)) {
      cancellationReasons.value = res.data.data
    }
  } catch (e) {
    console.warn('Gagal memuat master alasan pembatalan:', e)
  } finally {
    loadingReasons.value = false
  }
}

const openCancelModal = () => {
  cancelError.value = ''
  otpSent.value = false
  if (otpCountdownTimer) clearInterval(otpCountdownTimer)
  otpCountdown.value = 0

  cancelForm.value = {
    reason_id: '',
    reason: '',
    remark: '',
    refund_email: orderData.value?.order?.client?.email || '',
    refund_bank_name: 'BCA',
    custom_bank_name: '',
    refund_bank_account_number: '',
    refund_bank_account_name: orderData.value?.order?.client?.name || '',
    otp: ''
  }

  fetchCancellationReasons()
  showCancelModal.value = true
}

const closeCancelModal = () => {
  if (cancelSubmitting.value) return
  showCancelModal.value = false
}

const onReasonChange = () => {
  const r = selectedReason.value
  cancelForm.value.reason = r ? r.reason : ''
  if (!isRemarkRequired.value) {
    cancelForm.value.remark = ''
  }
}

const sendCancelOtp = async () => {
  if (!isRefundBankFormValid.value || sendingOtp.value || !orderData.value?.order) return

  try {
    sendingOtp.value = true
    cancelError.value = ''

    const effectiveBank = cancelForm.value.refund_bank_name === 'Lainnya'
      ? cancelForm.value.custom_bank_name.trim()
      : cancelForm.value.refund_bank_name

    const orderNum = orderData.value.order.order_number
    const res = await axios.post(`/api/v1/orders/${orderNum}/cancel-otp`, {
      email: cancelForm.value.refund_email.trim(),
      bank_name: effectiveBank,
      account_number: cancelForm.value.refund_bank_account_number.trim(),
      account_name: cancelForm.value.refund_bank_account_name.trim(),
    })

    if (res.data?.success) {
      otpSent.value = true
      startOtpCountdown(60)
      if (res.data.debug_otp) {
        cancelForm.value.otp = res.data.debug_otp
      }
    } else {
      cancelError.value = res.data?.message || 'Gagal mengirim kode OTP.'
    }
  } catch (err) {
    cancelError.value = err.response?.data?.message || 'Gagal mengirim kode OTP. Pastikan email valid.'
  } finally {
    sendingOtp.value = false
  }
}

const startOtpCountdown = (seconds) => {
  if (otpCountdownTimer) clearInterval(otpCountdownTimer)
  otpCountdown.value = seconds
  otpCountdownTimer = setInterval(() => {
    otpCountdown.value--
    if (otpCountdown.value <= 0) {
      clearInterval(otpCountdownTimer)
    }
  }, 1000)
}

const submitCancelOrder = async () => {
  if (!isCancelFormValid.value || cancelSubmitting.value || !orderData.value?.order) return

  try {
    cancelSubmitting.value = true
    cancelError.value = ''

    const effectiveBank = cancelForm.value.refund_bank_name === 'Lainnya'
      ? cancelForm.value.custom_bank_name.trim()
      : cancelForm.value.refund_bank_name

    const payload = {
      reason_id: cancelForm.value.reason_id,
      reason: cancelForm.value.reason,
      remark: cancelForm.value.remark ? cancelForm.value.remark.trim() : null,
      refund_email: isMidtransOrPaidOrder.value ? cancelForm.value.refund_email.trim() : null,
      refund_bank_name: isMidtransOrPaidOrder.value ? effectiveBank : null,
      refund_bank_account_number: isMidtransOrPaidOrder.value ? cancelForm.value.refund_bank_account_number.trim() : null,
      refund_bank_account_name: isMidtransOrPaidOrder.value ? cancelForm.value.refund_bank_account_name.trim() : null,
      otp: isMidtransOrPaidOrder.value ? cancelForm.value.otp.trim() : null,
    }

    const orderNum = orderData.value.order.order_number
    const res = await axios.post(`/api/v1/orders/${orderNum}/cancel`, payload)

    if (res.data?.success) {
      try {
        localStorage.removeItem('naumil_guest_pending_order')
      } catch (e) {}

      if (res.data.data?.order) {
        orderData.value.order = res.data.data.order
      } else {
        orderData.value.order.status = 'Order Reject'
        orderData.value.order.rejection_reason = payload.reason
      }
      orderData.value.is_rejected = true
      orderData.value.rejection_reason = res.data.data?.rejection_reason || payload.reason
      orderData.value.cancellation_reason = payload.reason
      orderData.value.cancellation_remark = payload.remark
      orderData.value.refund_bank_name = payload.refund_bank_name
      orderData.value.refund_bank_account_number = payload.refund_bank_account_number
      orderData.value.refund_bank_account_name = payload.refund_bank_account_name
      orderData.value.refund_status = res.data.data?.refund_status || (isMidtransOrPaidOrder.value ? 'pending' : null)

      showCancelModal.value = false
      alert(res.data.message || 'Pesanan berhasil dibatalkan.')
    } else {
      cancelError.value = res.data?.message || 'Gagal membatalkan pesanan.'
    }
  } catch (err) {
    cancelError.value = err.response?.data?.message || 'Terjadi kesalahan saat membatalkan pesanan.'
  } finally {
    cancelSubmitting.value = false
  }
}

onMounted(() => {
  if (props.orderNumber) {
    loadTrace(props.orderNumber)
  }

  // Auto open cancel modal if action=cancel in URL query
  setTimeout(() => {
    if (typeof window !== 'undefined') {
      const urlParams = new URLSearchParams(window.location.search)
      if (urlParams.get('action') === 'cancel') {
        openCancelModal()
      }
    }
  }, 1000)

  // 3-second live heartbeat polling backup
  pollTimer = setInterval(silentRefresh, 3000)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
  if (otpCountdownTimer) clearInterval(otpCountdownTimer)
  if (pusherInstance) {
    try {
      if (currentSubscribedOrder) pusherInstance.unsubscribe(`order.${currentSubscribedOrder}`)
      if (globalOrdersChannel) pusherInstance.unsubscribe('orders')
      pusherInstance.disconnect()
    } catch (e) {}
  }
})
</script>

<style scoped>
.page-title {
  font-size: 2.2rem;
  margin-bottom: 0.5rem;
}

.search-box {
  display: flex;
  gap: 8px;
  max-width: 480px;
}

.max-w-600 { max-width: 600px; }
.max-w-800 { max-width: 800px; }

/* Alert Reject */
.alert-reject {
  background: #fef2f2;
  border: 1.5px solid #fecaca;
}

/* Pipeline Timeline */
.pipeline-container {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.pipeline-step {
  display: flex;
  gap: 1.25rem;
  position: relative;
}

.step-icon-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.step-icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  background: #f1f5f9;
  color: #94a3b8;
  border: 2px solid var(--border);
  transition: var(--transition);
  z-index: 2;
}

.step-line {
  width: 2px;
  flex: 1;
  background: var(--border);
  margin-top: 6px;
  margin-bottom: -14px;
}

.pipeline-step.completed .step-icon {
  background: #dcfce7;
  color: #16a34a;
  border-color: #22c55e;
}
.pipeline-step.completed .step-line {
  background: #22c55e;
}

.pipeline-step.active .step-icon {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
  box-shadow: 0 0 0 6px rgba(225, 29, 72, 0.15);
  animation: pulseAnim 1.5s infinite;
}

.step-details {
  flex: 1;
  padding-bottom: 0.5rem;
}

.step-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--dark);
}

.step-desc {
  font-size: 0.86rem;
  color: var(--text-muted);
  margin-top: 4px;
  margin-bottom: 0;
}

/* Items List */
.items-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.item-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #f1f5f9;
}

.item-qty-badge {
  background: #f1f5f9;
  color: var(--primary);
  font-weight: 800;
  font-size: 0.95rem;
  padding: 4px 8px;
  border-radius: var(--radius-sm);
  display: inline-block;
}

.qris-img {
  width: 200px;
  height: 200px;
  object-fit: contain;
}

.live-pulse {
  animation: pulseAnim 1.2s infinite;
}

@keyframes pulseAnim {
  0% { opacity: 1; }
  50% { opacity: 0.4; }
  100% { opacity: 1; }
}

/* ================= CANCEL MODAL STYLES ================= */
.cancel-modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(15, 23, 42, 0.7);
  backdrop-filter: blur(5px);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.cancel-modal-dialog {
  max-width: 580px;
  width: 100%;
  background: #ffffff;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.cancel-modal-header {
  padding: 18px 24px;
  border-bottom: 1px solid #f1f5f9;
  background: #ffffff;
}

.cancel-icon-circle {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: #fee2e2;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.btn-close-modal {
  background: transparent;
  border: none;
  font-size: 1.3rem;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.15s;
}

.btn-close-modal:hover {
  color: #0f172a;
}

.cancel-modal-body {
  overflow-y: auto;
}

.form-select-custom, .form-control-custom {
  border-radius: 10px;
  border: 1px solid #cbd5e1;
  padding: 10px 14px;
  font-size: 0.95rem;
}

.form-select-custom:focus, .form-control-custom:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15);
}

.refund-notice-box {
  background: #fffbeb;
  border: 1px solid #fef3c7;
}

.otp-box {
  background: #f8fafc;
}

.animate-scale-in {
  animation: scaleIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.animate-fade-in {
  animation: fadeIn 0.2s ease;
}

@keyframes scaleIn {
  from { opacity: 0; transform: scale(0.95) translateY(10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
</style>
