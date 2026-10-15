<template>
  <AppLayout>
    <div class="storefront-page">
      <!-- Store Hero Header -->
      <section class="store-hero py-4 bg-white border-bottom">
        <div class="container">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
              <img :src="store.logo || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=120&q=80'" 
                   :alt="store.name" class="store-hero-logo shadow-sm" />
              <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                  <h1 class="store-hero-title mb-0">{{ store.name }}</h1>
                  <span class="badge badge-dark font-mono">{{ store.code }}</span>
                </div>
                <p class="text-muted small mb-1">
                  <i class="fa-solid fa-location-dot text-danger me-1"></i> {{ store.address }}
                </p>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge badge-green">
                    <i class="fa-solid fa-circle me-1" style="font-size: 0.55rem;"></i> Dapur Buka & Siap Order
                  </span>
                  <span class="badge badge-primary font-mono" style="font-size: 0.72rem;">
                    <i class="fa-solid fa-qrcode me-1"></i> QRIS Dinamis Cabang
                  </span>
                </div>
              </div>
            </div>

            <!-- Header Quick Actions -->
            <div class="d-flex flex-wrap gap-2">
              <Link href="/" class="btn-secondary" title="Ganti ke cabang lain">
                <i class="fa-solid fa-store me-1"></i> Ganti Cabang
              </Link>
              <a v-if="store.phone" :href="'https://wa.me/' + cleanPhone(store.phone)" target="_blank" class="btn-secondary">
                <i class="fa-brands fa-whatsapp text-success me-1"></i> WhatsApp
              </a>
              <!-- Backend Cabang (Hanya dimunculkan untuk Admin Cabang / Super Admin) -->
              <a v-if="store.can_manage_backend" :href="store.backend_url" target="_blank" class="btn-primary" title="Masuk ke Backend Toko Ini">
                <i class="fa-solid fa-gauge me-1"></i> Backend Cabang
              </a>
            </div>
          </div>
        </div>
      </section>

      <!-- Active Campaign Banner Section (if any) -->
      <section v-if="campaigns && campaigns.length > 0" class="campaign-spotlight py-4 bg-light">
        <div class="container">
          <div v-for="camp in campaigns" :key="camp.id" class="card campaign-card mb-3 p-0 overflow-hidden border">
            <div class="row g-0 align-items-center">
              <div class="col-md-4">
                <img :src="camp.banner_image || 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&q=80'" 
                     :alt="camp.title" class="camp-banner-img" />
              </div>
              <div class="col-md-8 p-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <span class="badge badge-amber font-mono">
                    <i class="fa-solid fa-bullhorn me-1"></i> BATCH PRE-ORDER AKTIF
                  </span>
                  <span class="badge badge-green font-mono">
                    Close: {{ formatDate(camp.close_order_at) }}
                  </span>
                </div>
                <h3 class="fw-bold fs-5 mb-2">{{ camp.title }}</h3>
                <p class="text-muted small mb-3">{{ camp.description || 'Pesan varian kuliner favorit dalam batch PO ini untuk dimasak fresh.' }}</p>
                <div class="d-flex align-items-center gap-3">
                  <span class="small text-danger fw-bold">
                    <i class="fa-solid fa-truck-fast me-1"></i> Siap / Kirim: {{ formatDate(camp.ready_at) }}
                  </span>
                  <Link :href="'/campaign/' + camp.qr_token" class="btn-sm btn-outline-primary text-decoration-none">
                    <i class="fa-solid fa-qrcode me-1"></i> Buka Halaman Khusus Batch PO
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Menus Section -->
      <section class="menus-section py-5">
        <div class="container">
          <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
              <span class="badge badge-primary mb-1">KATALOG MAKANAN & MINUMAN</span>
              <h2 class="section-title mb-0">Menu Pilihan Cabang {{ store.name }}</h2>
            </div>

            <!-- Category Filter Tabs -->
            <div class="category-pills d-flex flex-wrap gap-2">
              <button :class="['cat-btn', activeCategory === null ? 'active' : '']" @click="activeCategory = null">
                Semua Menu ({{ menus.length }})
              </button>
              <button v-for="cat in categories" :key="cat.id" 
                      :class="['cat-btn', activeCategory === cat.id ? 'active' : '']" 
                      @click="activeCategory = cat.id">
                {{ cat.name }} ({{ cat.menus ? cat.menus.length : 0 }})
              </button>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredMenus.length === 0" class="text-center py-5">
            <i class="fa-solid fa-bowl-food fa-3x text-muted mb-3"></i>
            <h5>Belum ada menu di kategori ini</h5>
            <p class="text-muted">Silakan pilih kategori lain atau cek kembali nanti.</p>
          </div>

          <!-- Menu Grid -->
          <div v-else class="menus-grid">
            <div v-for="menu in filteredMenus" :key="menu.id" class="card menu-card">
              <div class="menu-img-wrapper">
                <img :src="menu.image || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80'" 
                     :alt="menu.name" class="menu-img" />
                <span class="badge badge-dark menu-badge font-mono">
                  {{ menu.category?.name || 'Signature' }}
                </span>
                <span v-if="menu.current_stock < 10" class="badge badge-red menu-stock-badge">
                  Sisa {{ menu.current_stock }}
                </span>
              </div>

              <div class="menu-card-body p-3 d-flex flex-column justify-content-between flex-grow-1">
                <div>
                  <h4 class="menu-title mb-1">{{ menu.name }}</h4>
                  <p class="menu-desc text-muted small mb-3">{{ menu.description || 'Sajian lezat dibuat fresh sesuai pesanan.' }}</p>
                </div>

                <div>
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                      <span class="text-muted small d-block">Harga Porsi</span>
                      <strong class="fs-5 text-dark font-mono">Rp {{ formatPrice(menu.price) }}</strong>
                    </div>
                    <span class="badge badge-light border small text-muted">
                      Stok: {{ menu.current_stock }}
                    </span>
                  </div>

                  <button class="btn-primary w-100 py-2 d-flex justify-content-center align-items-center gap-2" 
                          @click="openCustomizeModal(menu)" :disabled="menu.current_stock <= 0">
                    <i class="fa-solid fa-cart-plus"></i>
                    <span>{{ menu.current_stock > 0 ? 'Pesan / Kustomisasi' : 'Stok Habis' }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Floating Cart Button -->
      <div v-if="cartItems.length > 0" class="floating-cart-bar">
        <div class="container d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-3">
            <div class="cart-icon-bubble">
              <i class="fa-solid fa-bag-shopping"></i>
              <span class="bubble-count">{{ cartTotalQuantity }}</span>
            </div>
            <div>
              <div class="fw-bold text-white fs-6">Total Belanja: Rp {{ formatPrice(cartTotalPrice) }}</div>
              <small class="text-light opacity-75">{{ cartItems.length }} varian menu dipilih</small>
            </div>
          </div>
          <button class="btn btn-light fw-bold px-4 py-2" @click="showCartModal = true">
            <i class="fa-solid fa-arrow-right me-1"></i> Lihat Keranjang & Checkout
          </button>
        </div>
      </div>

      <!-- Modal Customization (Topping, Upsize, Condition) -->
      <div v-if="selectedMenuModal" class="modal-backdrop-custom" @click.self="closeCustomizeModal">
        <div class="modal-card-custom">
          <div class="modal-header-custom d-flex justify-content-between align-items-center p-3 border-bottom">
            <h5 class="fw-bold mb-0">Kustomisasi Menu</h5>
            <button class="btn-close-custom" @click="closeCustomizeModal"><i class="fa-solid fa-xmark"></i></button>
          </div>

          <div class="modal-body-custom p-4">
            <div class="d-flex gap-3 mb-4">
              <img :src="selectedMenuModal.image || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=200&q=80'" 
                   alt="Menu" class="modal-menu-img rounded" />
              <div>
                <h4 class="fw-bold mb-1">{{ selectedMenuModal.name }}</h4>
                <p class="text-muted small mb-2">{{ selectedMenuModal.description }}</p>
                <div class="fs-5 font-mono fw-bold text-danger">Rp {{ formatPrice(selectedMenuModal.price) }}</div>
              </div>
            </div>

            <!-- Options: Upsize / Porsi -->
            <div v-if="upsizeOptions.length > 0" class="option-section mb-4">
              <h6 class="fw-bold mb-2"><i class="fa-solid fa-maximize me-1 text-primary"></i> Pilihan Ukuran / Porsi:</h6>
              <div class="d-flex flex-column gap-2">
                <label v-for="opt in upsizeOptions" :key="opt.id" class="option-radio-card">
                  <input type="radio" :value="opt" v-model="selectedUpsize" name="upsize" />
                  <span class="flex-grow-1 ms-2">{{ opt.name }}</span>
                  <strong class="font-mono">{{ opt.price > 0 ? '+ Rp ' + formatPrice(opt.price) : 'Standard' }}</strong>
                </label>
              </div>
            </div>

            <!-- Options: Level Pedas / Kondisi -->
            <div v-if="conditionOptions.length > 0" class="option-section mb-4">
              <h6 class="fw-bold mb-2"><i class="fa-solid fa-fire me-1 text-danger"></i> Tingkat Kepedasan / Catatan Kondisi:</h6>
              <div class="d-flex flex-column gap-2">
                <label v-for="opt in conditionOptions" :key="opt.id" class="option-radio-card">
                  <input type="radio" :value="opt" v-model="selectedCondition" name="condition" />
                  <span class="flex-grow-1 ms-2">{{ opt.name }}</span>
                  <strong class="font-mono">{{ opt.price > 0 ? '+ Rp ' + formatPrice(opt.price) : 'Gratis' }}</strong>
                </label>
              </div>
            </div>

            <!-- Options: Toppings -->
            <div v-if="toppingOptions.length > 0" class="option-section mb-4">
              <h6 class="fw-bold mb-2"><i class="fa-solid fa-plus-circle me-1 text-success"></i> Topping Ekstra (Opsional):</h6>
              <div class="d-flex flex-column gap-2">
                <label v-for="opt in toppingOptions" :key="opt.id" class="option-checkbox-card">
                  <input type="checkbox" :value="opt" v-model="selectedToppings" />
                  <span class="flex-grow-1 ms-2">{{ opt.name }}</span>
                  <strong class="font-mono text-success">+ Rp {{ formatPrice(opt.price) }}</strong>
                </label>
              </div>
            </div>

            <!-- Special Notes -->
            <div class="mb-4">
              <label class="form-label small fw-bold">Catatan Khusus untuk Dapur Cabang:</label>
              <textarea v-model="modalSpecialNotes" rows="2" class="form-input" 
                        placeholder="Contoh: Pisahkan sambal, jangan pakai bawang goreng..."></textarea>
            </div>

            <!-- Quantity Selector -->
            <div class="d-flex justify-content-between align-items-center p-3 rounded bg-light border">
              <div>
                <span class="small text-muted d-block">Jumlah Pesanan:</span>
                <strong class="fs-5 font-mono text-dark">Rp {{ formatPrice(modalCalculatedPrice) }}</strong>
              </div>
              <div class="d-flex align-items-center gap-2">
                <button class="btn-qty" @click="modalQty = Math.max(1, modalQty - 1)">-</button>
                <span class="fw-bold font-mono px-3 fs-5">{{ modalQty }}</span>
                <button class="btn-qty" @click="modalQty++">+</button>
              </div>
            </div>
          </div>

          <div class="modal-footer-custom p-3 border-top d-flex gap-2">
            <button class="btn-secondary flex-grow-1" @click="closeCustomizeModal">Batal</button>
            <button class="btn-primary flex-grow-1" @click="addConfiguredItemToCart">
              <i class="fa-solid fa-cart-plus me-1"></i> Masukkan ke Keranjang
            </button>
          </div>
        </div>
      </div>

      <!-- Slide-over / Modal Keranjang & Checkout -->
      <div v-if="showCartModal" class="modal-backdrop-custom" @click.self="showCartModal = false">
        <div class="modal-card-custom cart-modal-box">
          <div class="modal-header-custom d-flex justify-content-between align-items-center p-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
              <i class="fa-solid fa-bag-shopping text-danger fs-5"></i>
              <h5 class="fw-bold mb-0">Keranjang Belanja ({{ store.name }})</h5>
            </div>
            <button class="btn-close-custom" @click="showCartModal = false"><i class="fa-solid fa-xmark"></i></button>
          </div>

          <div class="modal-body-custom p-4">
            <!-- Items in Cart -->
            <div class="cart-items-list mb-4">
              <div v-for="(item, idx) in cartItems" :key="idx" class="cart-item-row p-3 rounded bg-light border mb-2">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <div>
                    <h6 class="fw-bold mb-0 text-dark">{{ item.menu.name }}</h6>
                    <small class="text-muted d-block font-mono">Rp {{ formatPrice(item.unitPrice) }} x {{ item.qty }}</small>
                  </div>
                  <div class="text-end">
                    <strong class="font-mono text-dark fs-6">Rp {{ formatPrice(item.subtotal) }}</strong>
                    <button class="btn btn-sm text-danger p-0 d-block ms-auto mt-1" @click="removeCartItem(idx)">
                      <i class="fa-solid fa-trash-can"></i> Hapus
                    </button>
                  </div>
                </div>

                <!-- Detail toppings / upsize -->
                <div v-if="item.selectedOptions && item.selectedOptions.length > 0" class="mt-2 pt-2 border-top">
                  <div class="d-flex flex-wrap gap-1">
                    <span v-for="opt in item.selectedOptions" :key="opt.id || opt.name" class="badge bg-white text-dark border">
                      + {{ opt.name }} (Rp {{ formatPrice(opt.price) }})
                    </span>
                  </div>
                </div>
                <small v-if="item.notes" class="text-warning d-block mt-1">
                  <i class="fa-solid fa-note-sticky me-1"></i> {{ item.notes }}
                </small>
              </div>
            </div>

            <!-- Customer Details Form -->
            <div class="customer-form-section border-top pt-4">
              <h6 class="fw-bold mb-3"><i class="fa-solid fa-address-card text-primary me-2"></i> Data Pemesan & Pengiriman</h6>

              <div class="mb-3">
                <label class="form-label small fw-bold">Nama Lengkap Pemesan <span class="text-danger">*</span></label>
                <input type="text" v-model="customerForm.name" class="form-input" placeholder="Contoh: Muhammad Naufal" required />
              </div>

              <div class="row g-2 mb-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Nomor WhatsApp Aktif <span class="text-danger">*</span></label>
                  <input type="text" v-model="customerForm.whatsapp" class="form-input font-mono" placeholder="081298765432" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Email (Opsional)</label>
                  <input type="email" v-model="customerForm.email" class="form-input" placeholder="nama@gmail.com" />
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold">Alamat Pengiriman Lengkap <span class="text-danger">*</span></label>
                <textarea v-model="customerForm.address" rows="2" class="form-input" placeholder="Jalan, No. Rumah, RT/RW, Patokan..." required></textarea>
              </div>

              <!-- Payment Method Selector -->
              <div class="mb-4">
                <label class="form-label small fw-bold">Metode Pembayaran</label>
                <div class="d-flex flex-column gap-2">
                  <!-- Payment Gateway (Mayar.id) if activated by store -->
                  <label v-if="store.payment_type === 'payment_gateway'" class="option-radio-card">
                    <input type="radio" value="Payment Gateway (Mayar.id)" v-model="customerForm.paymentMethod" name="paymentMethod" />
                    <div class="flex-grow-1 ms-2">
                      <div class="fw-bold"><i class="fa-solid fa-credit-card text-success me-1"></i> Payment Gateway (Mayar.id)</div>
                      <small class="text-muted d-block">QRIS Otomatis, Virtual Account Bank & E-Wallet (Lunas Instan)</small>
                    </div>
                    <span class="badge badge-primary">Online Otomatis</span>
                  </label>

                  <!-- QRIS Statik / Dinamis if activated by store -->
                  <label v-else class="option-radio-card">
                    <input type="radio" value="QRIS Dinamis" v-model="customerForm.paymentMethod" name="paymentMethod" />
                    <div class="flex-grow-1 ms-2">
                      <div class="fw-bold"><i class="fa-solid fa-qrcode text-primary me-1"></i> QRIS Toko (Scan Mandiri)</div>
                      <small class="text-muted d-block">Scan QRIS menggunakan m-Banking atau E-Wallet apapun</small>
                    </div>
                    <span class="badge badge-primary">Rekomendasi</span>
                  </label>

                  <!-- COD option -->
                  <label class="option-radio-card">
                    <input type="radio" value="Bayar Terima Makanan" v-model="customerForm.paymentMethod" name="paymentMethod" />
                    <div class="flex-grow-1 ms-2">
                      <div class="fw-bold"><i class="fa-solid fa-hand-holding-dollar text-success me-1"></i> Bayar Terima Makanan (COD)</div>
                      <small class="text-muted d-block">Bayar tunai kepada kurir saat pesanan tiba</small>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Order Summary Box -->
              <div class="p-3 rounded bg-light border mb-3">
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted small">Cabang Penyedia:</span>
                  <span class="fw-bold">{{ store.name }} ({{ store.code }})</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted small">Total Pembayaran:</span>
                  <strong class="fs-5 font-mono text-danger">Rp {{ formatPrice(cartTotalPrice) }}</strong>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer-custom p-3 border-top d-flex gap-2">
            <button class="btn-secondary" @click="showCartModal = false">Lanjut Belanja</button>
            <button class="btn-primary flex-grow-1" @click="submitOrder" :disabled="submittingOrder">
              <i v-if="submittingOrder" class="fa-solid fa-spinner fa-spin me-1"></i>
              <i v-else class="fa-solid fa-paper-plane me-1"></i>
              <span>{{ submittingOrder ? 'Memproses Pesanan...' : 'Konfirmasi & Buat Pesanan' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Order Success / QRIS / Payment Gateway Popup Modal -->
      <div v-if="createdOrderData" class="modal-backdrop-custom">
        <div class="modal-card-custom text-center p-4 max-w-500">
          <div class="success-icon-bubble mb-3 mx-auto">
            <i class="fa-solid fa-circle-check"></i>
          </div>

          <h3 class="fw-bold mb-1">Pesanan Berhasil Dibuat!</h3>
          <p class="text-muted small mb-3">Pesanan Anda telah masuk ke sistem antrean dapur {{ store.name }}.</p>

          <div class="p-3 rounded bg-light border mb-3">
            <small class="text-muted d-block">Nomor Pesanan (Order ID):</small>
            <strong class="fs-4 font-mono text-danger">{{ createdOrderData.order_number }}</strong>
          </div>

          <!-- Mayar.id Payment Gateway Pay Button if payment_url is returned -->
          <div v-if="createdOrderData.payment_url" class="p-3 border rounded bg-white mb-3 shadow-sm text-start">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-credit-card text-success me-1"></i> Pembayaran Online Mayar.id</h6>
              <span class="badge bg-success text-white">Menunggu Pembayaran</span>
            </div>
            <p class="text-muted small mb-3">
              Silakan selesaikan pembayaran tagihan ini secara aman melalui portal Mayar.id (QRIS otomatis, Virtual Account, & E-Wallet):
            </p>
            <a :href="createdOrderData.payment_url" target="_blank" class="btn btn-danger w-100 py-2 fw-bold text-white fs-6 d-flex align-items-center justify-content-center gap-2 mb-2 shadow-sm text-decoration-none">
              <i class="fa-solid fa-arrow-up-right-from-square"></i>
              <span>Bayar Sekarang (Rp {{ formatPrice(createdOrderData.total_amount) }})</span>
            </a>
            <small class="text-muted d-block text-center mt-1">Setelah dibayar, status pesanan otomatis berubah jadi <strong>Diproses</strong>.</small>
          </div>

          <!-- Dynamic QRIS Display if QRIS payment -->
          <div v-else-if="createdOrderData.payment_method === 'QRIS Dinamis' || createdOrderData.payment_method === 'QRIS Statik'" class="qris-payment-box p-3 border rounded bg-white mb-3">
            <h6 class="fw-bold mb-2"><i class="fa-solid fa-qrcode text-primary me-1"></i> Scan QRIS Toko</h6>
            <div class="p-2 bg-white d-inline-block border rounded mb-2">
              <img :src="store.qris_image || dynamicQrisUrl" alt="QRIS Toko" class="img-fluid" style="max-width: 220px;" />
            </div>
            <div class="font-mono fw-bold fs-5 text-success">
              Rp {{ formatPrice(createdOrderData.total_amount) }}
            </div>
            <small class="text-muted d-block mt-1">Scan menggunakan BCA, Gopay, OVO, ShopeePay, atau m-Banking apapun.</small>
          </div>

          <!-- Fallback if Mayar Payment Gateway selected but payment_url failed to generate -->
          <div v-else-if="createdOrderData.payment_method === 'Payment Gateway' || createdOrderData.payment_method === 'Payment Gateway (Mayar.id)'" class="p-3 border rounded bg-warning-subtle text-warning-emphasis mb-3 text-start shadow-sm">
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="fa-solid fa-triangle-exclamation text-warning fs-5"></i>
              <h6 class="fw-bold mb-0">Link Pembayaran Mayar Belum Tersedia</h6>
            </div>
            <p class="small mb-2">
              {{ createdOrderData.payment_message || 'Invoice online belum berhasil dibuat oleh sistem gateway. Periksa izin API Key toko di dashboard Mayar.' }}
            </p>
            <small class="text-muted d-block">Pesanan tetap tercatat di sistem dapur. Silakan konfirmasi via WhatsApp toko untuk menyelesaikan pembayaran.</small>
          </div>

          <div class="d-flex flex-column gap-2 mt-3">
            <Link :href="'/trace/' + createdOrderData.order_number" class="btn-primary py-2 text-center">
              <i class="fa-solid fa-radar me-1"></i> Lacak Status Pesanan (Live KDS)
            </Link>
            <a v-if="store.phone" :href="'https://wa.me/' + cleanPhone(store.phone) + '?text=' + encodeURIComponent('Halo ' + store.name + ', saya telah membuat pesanan ' + createdOrderData.order_number + ' dengan total Rp ' + formatPrice(createdOrderData.total_amount))" 
               target="_blank" class="btn-secondary py-2 text-center">
              <i class="fa-brands fa-whatsapp text-success me-1"></i> Konfirmasi ke WhatsApp Toko
            </a>
            <button class="btn btn-link text-muted small mt-1" @click="resetAndCloseSuccessModal">
              Kembali ke Toko
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  store: {
    type: Object,
    required: true
  },
  categories: {
    type: Array,
    default: () => []
  },
  menus: {
    type: Array,
    default: () => []
  },
  campaigns: {
    type: Array,
    default: () => []
  },
  otherStores: {
    type: Array,
    default: () => []
  }
})

const activeCategory = ref(null)

const filteredMenus = computed(() => {
  if (activeCategory.value === null) {
    return props.menus
  }
  return props.menus.filter(m => m.category_id === activeCategory.value)
})

// Customization Modal State
const selectedMenuModal = ref(null)
const selectedToppings = ref([])
const selectedUpsize = ref(null)
const selectedCondition = ref(null)
const modalSpecialNotes = ref('')
const modalQty = ref(1)

const toppingOptions = computed(() => {
  if (!selectedMenuModal.value?.options) return []
  return selectedMenuModal.value.options.filter(o => o.type === 'topping')
})

const upsizeOptions = computed(() => {
  if (!selectedMenuModal.value?.options) return []
  return selectedMenuModal.value.options.filter(o => o.type === 'upsize')
})

const conditionOptions = computed(() => {
  if (!selectedMenuModal.value?.options) return []
  return selectedMenuModal.value.options.filter(o => o.type === 'condition')
})

const openCustomizeModal = (menu) => {
  selectedMenuModal.value = menu
  selectedToppings.value = []
  selectedUpsize.value = upsizeOptions.value.length > 0 ? upsizeOptions.value[0] : null
  selectedCondition.value = conditionOptions.value.length > 0 ? conditionOptions.value[0] : null
  modalSpecialNotes.value = ''
  modalQty.value = 1
}

const closeCustomizeModal = () => {
  selectedMenuModal.value = null
}

const modalCalculatedPrice = computed(() => {
  if (!selectedMenuModal.value) return 0
  let base = Number(selectedMenuModal.value.price) || 0
  if (selectedUpsize.value) {
    base += Number(selectedUpsize.value.price) || 0
  }
  if (selectedCondition.value) {
    base += Number(selectedCondition.value.price) || 0
  }
  selectedToppings.value.forEach(t => {
    base += Number(t.price) || 0
  })
  return base * modalQty.value
})

// Cart State
const cartItems = ref([])
const showCartModal = ref(false)

const addConfiguredItemToCart = () => {
  if (!selectedMenuModal.value) return

  const options = []
  if (selectedUpsize.value && selectedUpsize.value.price > 0) options.push(selectedUpsize.value)
  if (selectedCondition.value && selectedCondition.value.price > 0) options.push(selectedCondition.value)
  selectedToppings.value.forEach(t => options.push(t))

  let unitPrice = Number(selectedMenuModal.value.price) || 0
  if (selectedUpsize.value) unitPrice += Number(selectedUpsize.value.price) || 0
  if (selectedCondition.value) unitPrice += Number(selectedCondition.value.price) || 0
  selectedToppings.value.forEach(t => unitPrice += Number(t.price) || 0)

  cartItems.value.push({
    menu: selectedMenuModal.value,
    qty: modalQty.value,
    unitPrice: unitPrice,
    subtotal: unitPrice * modalQty.value,
    selectedOptions: options,
    notes: modalSpecialNotes.value
  })

  closeCustomizeModal()
  showCartModal.value = true
}

const removeCartItem = (idx) => {
  cartItems.value.splice(idx, 1)
  if (cartItems.value.length === 0) {
    showCartModal.value = false
  }
}

const cartTotalQuantity = computed(() => {
  return cartItems.value.reduce((acc, it) => acc + it.qty, 0)
})

const cartTotalPrice = computed(() => {
  return cartItems.value.reduce((acc, it) => acc + it.subtotal, 0)
})

// Checkout & Submit Order
const customerForm = ref({
  name: '',
  whatsapp: '',
  email: '',
  address: '',
  paymentMethod: props.store.payment_type === 'payment_gateway' ? 'Payment Gateway (Mayar.id)' : 'QRIS Dinamis'
})

const submittingOrder = ref(false)
const createdOrderData = ref(null)

const submitOrder = async () => {
  if (!customerForm.value.name || !customerForm.value.whatsapp || !customerForm.value.address) {
    alert('Mohon lengkapi Nama, Nomor WhatsApp, dan Alamat Pengiriman.')
    return
  }

  if (cartItems.value.length === 0) {
    alert('Keranjang belanja Anda masih kosong.')
    return
  }

  try {
    submittingOrder.value = true

    const payload = {
      store_id: props.store.id,
      campaign_id: props.campaigns && props.campaigns.length > 0 ? props.campaigns[0].id : null,
      client: {
        name: customerForm.value.name,
        whatsapp: customerForm.value.whatsapp,
        email: customerForm.value.email || (customerForm.value.whatsapp + '@naumilrice.com'),
        address: customerForm.value.address,
      },
      payment_method: customerForm.value.paymentMethod,
      notes: 'Order via Storefront ' + props.store.name,
      items: cartItems.value.map(it => ({
        menu_id: it.menu.id,
        quantity: it.qty,
        selected_options: it.selectedOptions,
        special_notes: it.notes
      }))
    }

    const res = await axios.post('/api/v1/orders', payload)
    if (res.data.success) {
      createdOrderData.value = res.data.data
      cartItems.value = []
      showCartModal.value = false
    } else {
      alert(res.data.message || 'Gagal membuat pesanan.')
    }
  } catch (err) {
    console.error('Order error', err)
    alert(err.response?.data?.message || 'Terjadi kesalahan saat memproses pesanan Anda.')
  } finally {
    submittingOrder.value = false
  }
}

const dynamicQrisUrl = computed(() => {
  if (!createdOrderData.value) return ''
  const qrisData = props.store.qris_data || ('00020101021226600014ID.LINKAJA.WWW01189360091800000000010215' + props.store.code)
  return `https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=${encodeURIComponent(qrisData + createdOrderData.value.order_number)}`
})

const resetAndCloseSuccessModal = () => {
  createdOrderData.value = null
}

const formatPrice = (val) => {
  return new Intl.NumberFormat('id-ID').format(val || 0)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const cleanPhone = (phone) => {
  if (!phone) return ''
  return phone.replace(/[^0-9]/g, '').replace(/^0/, '62')
}
</script>

<style scoped>
.store-hero-logo {
  width: 76px;
  height: 76px;
  border-radius: 18px;
  object-fit: cover;
  border: 2px solid #e2e8f0;
}

.store-hero-title {
  font-size: 1.8rem;
  font-weight: 800;
  color: #0f172a;
}

.camp-banner-img {
  width: 100%;
  height: 180px;
  object-fit: cover;
}

.category-pills {
  display: flex;
  overflow-x: auto;
  padding-bottom: 4px;
}

.cat-btn {
  padding: 8px 16px;
  border-radius: 9999px;
  font-size: 0.88rem;
  font-weight: 600;
  background: white;
  border: 1px solid #cbd5e1;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.cat-btn:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.cat-btn.active {
  background: var(--primary, #e11d48);
  border-color: var(--primary, #e11d48);
  color: white;
  box-shadow: 0 4px 12px rgba(225, 29, 72, 0.3);
}

.menus-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
}

.menu-card {
  border-radius: 18px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
  display: flex;
  flex-direction: column;
  transition: transform 0.2s, box-shadow 0.2s;
}

.menu-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
}

.menu-img-wrapper {
  position: relative;
  height: 190px;
  background: #f8fafc;
}

.menu-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.menu-badge {
  position: absolute;
  top: 10px;
  left: 10px;
}

.menu-stock-badge {
  position: absolute;
  top: 10px;
  right: 10px;
}

.menu-title {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
}

.menu-desc {
  line-height: 1.4;
  height: 2.8em;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

/* Floating Cart Bar */
.floating-cart-bar {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  width: calc(100% - 40px);
  max-width: 900px;
  background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
  padding: 0.85rem 1.5rem;
  border-radius: 9999px;
  box-shadow: 0 10px 30px rgba(225, 29, 72, 0.4);
  z-index: 1000;
}

.cart-icon-bubble {
  position: relative;
  width: 44px;
  height: 44px;
  background: white;
  color: #e11d48;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.bubble-count {
  position: absolute;
  top: -4px;
  right: -4px;
  background: #0f172a;
  color: white;
  font-size: 0.72rem;
  font-weight: 800;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Modal styles */
.modal-backdrop-custom {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 1rem;
}

.modal-card-custom {
  background: white;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  border-radius: 20px;
  overflow-y: auto;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
  display: flex;
  flex-direction: column;
}

.cart-modal-box {
  max-width: 680px;
}

.modal-menu-img {
  width: 90px;
  height: 90px;
  object-fit: cover;
}

.option-radio-card, .option-checkbox-card {
  display: flex;
  align-items: center;
  padding: 0.65rem 0.85rem;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  cursor: pointer;
  transition: all 0.2s;
}

.option-radio-card:hover, .option-checkbox-card:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.btn-qty {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: white;
  font-weight: 800;
  font-size: 1.1rem;
  cursor: pointer;
}

.btn-close-custom {
  background: transparent;
  border: none;
  font-size: 1.25rem;
  color: #64748b;
  cursor: pointer;
}

.success-icon-bubble {
  width: 68px;
  height: 68px;
  border-radius: 50%;
  background: #dcfce7;
  color: #16a34a;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.25rem;
}
</style>
