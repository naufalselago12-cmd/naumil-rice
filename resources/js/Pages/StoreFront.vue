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
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <span class="badge badge-green">
                    <i class="fa-solid fa-circle me-1" style="font-size: 0.55rem;"></i> Dapur Buka & Siap Order
                  </span>
                  <span class="badge badge-primary font-mono" style="font-size: 0.72rem;">
                    <i class="fa-solid fa-qrcode me-1"></i> QRIS Dinamis Cabang
                  </span>
                  <!-- Customer Status Badge with Profile Link -->
                  <Link v-if="isCustomerLoggedIn" href="/customer/profile" class="badge bg-warning text-dark font-mono d-flex align-items-center gap-1 shadow-sm text-decoration-none" title="Klik untuk Kunjungi Profil & Riwayat Poin">
                    <i class="fa-solid fa-crown text-dark"></i>
                    <span>{{ customerConcat }}</span>
                    <span class="badge bg-dark text-warning ms-1">{{ customerPoints }} Pts</span>
                    <i class="fa-solid fa-arrow-up-right-from-square ms-1" style="font-size: 0.65rem;"></i>
                  </Link>
                </div>
              </div>
            </div>

            <!-- Header Quick Actions -->
            <div class="d-flex flex-wrap gap-2">
              <Link v-if="isCustomerLoggedIn" href="/customer/profile" class="btn btn-sm btn-warning text-dark fw-bold" title="Kunjungi Profil & Saldo Poin">
                <i class="fa-solid fa-crown me-1"></i> Profil Pelanggan Setia ({{ customerPoints }} Pts)
              </Link>
              <Link v-else href="/customer/profile" class="btn btn-sm btn-outline-warning text-dark fw-bold" title="Daftar atau Masuk Pelanggan Setia">
                <i class="fa-solid fa-crown text-warning me-1"></i> Masuk / Daftar Pelanggan Setia
              </Link>

              <button v-if="isCustomerLoggedIn" class="btn btn-sm btn-outline-secondary" @click="logoutCustomer" title="Keluar Akun">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Keluar
              </button>

              <Link href="/" class="btn-secondary" title="Ganti ke cabang lain">
                <i class="fa-solid fa-store me-1"></i> Ganti Cabang
              </Link>
              <a v-if="store.phone" :href="'https://wa.me/' + cleanPhone(store.phone)" target="_blank" class="btn-secondary">
                <i class="fa-brands fa-whatsapp text-success me-1"></i> WhatsApp
              </a>
              <a v-if="store.can_manage_backend" :href="store.backend_url" target="_blank" class="btn-primary" title="Masuk ke Backend Toko Ini">
                <i class="fa-solid fa-gauge me-1"></i> Backend Cabang
              </a>
            </div>
          </div>
        </div>
      </section>

      <!-- Loyalty Promos Banner (If Store Has Active Loyalty Promos) -->
      <section v-if="availablePromos.length > 0" class="py-3 bg-warning-subtle border-bottom">
        <div class="container">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <div class="badge bg-warning text-dark p-2 rounded-circle fs-6">
                <i class="fa-solid fa-crown"></i>
              </div>
              <div>
                <strong class="text-dark d-block">🌟 Program Spesial Pelanggan Setia Cabang {{ store.name }}!</strong>
                <small class="text-muted">
                  Nikmati diskon eksklusif dan kumpulkan Credit Point berlipat di setiap porsi menu untuk ditukar hadiah.
                </small>
              </div>
            </div>
            <div class="d-flex gap-2">
              <span v-for="p in availablePromos" :key="p.id" class="badge bg-white text-dark border shadow-sm py-2 px-3">
                <i class="fa-solid fa-tag text-danger me-1"></i>
                <strong>{{ p.title }}</strong>
                <span class="text-success ms-1 fw-bold">
                  {{ p.promo_type === 'all_menu_percent' ? p.discount_percent + '% OFF' : (p.promo_type === 'min_purchase_discount' ? 'Potongan Rp ' + formatPrice(p.discount_amount) : 'Promo Menu Khusus') }}
                </span>
              </span>
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
            <div class="category-pills">
              <button :class="['cat-btn', activeCategory === null ? 'active' : '']" @click="activeCategory = null">
                Semua Menu ({{ menus.length }})
              </button>
              <button v-for="cat in categories" :key="cat.id"
                      :class="['cat-btn', activeCategory === cat.id ? 'active' : '']"
                      @click="activeCategory = cat.id">
                {{ cat.name }} ({{ cat.menus?.length || 0 }})
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
                
                <!-- Loyalty Points Badge on Menu Card -->
                <span v-if="menu.reward_points > 0" class="badge bg-warning text-dark menu-points-badge shadow-sm">
                  <i class="fa-solid fa-coins me-1 text-warning"></i> +{{ menu.reward_points }} Pts
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
              <div class="fw-bold text-white fs-6">Total Belanja: Rp {{ formatPrice(cartFinalPrice) }}</div>
              <small class="text-light opacity-75">
                {{ cartItems.length }} varian menu dipilih
                <span v-if="finalEarnedPoints > 0" class="badge bg-warning text-dark ms-1">
                  +{{ finalEarnedPoints }} Pts
                </span>
              </small>
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
                <div v-if="selectedMenuModal.reward_points > 0" class="badge bg-warning-subtle text-dark border border-warning mt-1 font-mono">
                  <i class="fa-solid fa-coins text-warning me-1"></i> +{{ selectedMenuModal.reward_points * modalQty }} Pts Pelanggan Setia
                </div>
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
                  <strong class="font-mono">+ Rp {{ formatPrice(opt.price) }}</strong>
                </label>
              </div>
            </div>

            <!-- Special Notes -->
            <div class="mb-4">
              <label class="form-label small fw-bold"><i class="fa-solid fa-note-sticky text-warning me-1"></i> Catatan Khusus untuk Dapur:</label>
              <input type="text" v-model="modalSpecialNotes" class="form-input" placeholder="Contoh: Sambal dipisah, kuah banyakan..." />
            </div>

            <!-- Quantity & Calculated Price -->
            <div class="d-flex justify-content-between align-items-center p-3 rounded bg-light border">
              <div>
                <span class="text-muted small d-block">Subtotal Item:</span>
                <strong class="fs-5 text-primary font-mono">Rp {{ formatPrice(modalCalculatedPrice) }}</strong>
              </div>
              <div class="d-flex align-items-center gap-2">
                <button class="btn-qty" @click="modalQty = Math.max(1, modalQty - 1)">-</button>
                <span class="fs-5 font-mono fw-bold px-2">{{ modalQty }}</span>
                <button class="btn-qty" @click="modalQty++">+</button>
              </div>
            </div>
          </div>

          <div class="modal-footer-custom p-3 border-top d-flex gap-2">
            <button class="btn-secondary" @click="closeCustomizeModal">Batal</button>
            <button class="btn-primary flex-grow-1" @click="addConfiguredItemToCart">
              <i class="fa-solid fa-cart-plus me-1"></i> Tambahkan ke Keranjang
            </button>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- MODAL KERANJANG & CHECKOUT TERBARU (LOYALTY VS GUEST REFACTOR) -->
      <!-- ========================================================================= -->
      <div v-if="showCartModal" class="modal-backdrop-custom" @click.self="showCartModal = false">
        <div class="modal-card-custom cart-modal-box">
          <div class="modal-header-custom d-flex justify-content-between align-items-center p-3 border-bottom">
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-cart-shopping text-danger me-2"></i> Keranjang & Konfirmasi Pesanan</h5>
            <button class="btn-close-custom" @click="showCartModal = false"><i class="fa-solid fa-xmark"></i></button>
          </div>

          <div class="modal-body-custom p-4">
            <!-- 1. Cart Items List -->
            <div class="cart-items-list mb-4">
              <h6 class="fw-bold mb-3"><i class="fa-solid fa-utensils text-primary me-2"></i> Menu yang Dipilih ({{ cartItems.length }})</h6>
              
              <div v-for="(item, idx) in cartItems" :key="idx" class="p-3 mb-2 rounded bg-light border cart-item-row">
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <div class="flex-grow-1">
                    <strong class="fs-6">{{ item.menu.name }}</strong>
                    <div class="text-muted small font-mono">
                      {{ item.qty }} x Rp {{ formatPrice(item.unitPrice) }} = <span class="fw-bold text-dark">Rp {{ formatPrice(item.subtotal) }}</span>
                      <span v-if="item.menu.reward_points > 0" class="badge bg-warning-subtle text-dark border border-warning ms-1">
                        +{{ item.menu.reward_points * item.qty }} Pts
                      </span>
                    </div>
                  </div>
                  <button class="btn btn-sm btn-outline-danger py-0 px-2" @click="removeCartItem(idx)" title="Hapus">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </div>

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

            <!-- 2. PILIHAN METODE CHECKOUT: PELANGGAN SETIA vs BELI MAKANAN SAJA -->
            <div class="checkout-method-selector mb-4 border-top pt-4">
              <h6 class="fw-bold mb-2"><i class="fa-solid fa-user-check text-primary me-2"></i> Pilih Metode Pemesanan</h6>
              <p class="text-muted small mb-3">Pilih ingin mendapatkan keuntungan keanggotaan setia atau memesan langsung tanpa membuat akun.</p>

              <div class="row g-2 mb-3">
                <div class="col-sm-6">
                  <label :class="['p-3 border rounded h-100 d-block cursor-pointer', checkoutMode === 'loyalty' ? 'border-primary bg-primary-subtle shadow-sm' : 'bg-light']" style="cursor: pointer;">
                    <div class="d-flex align-items-center gap-2">
                      <input type="radio" value="loyalty" v-model="checkoutMode" name="checkoutMode">
                      <div>
                        <strong class="d-block text-primary"><i class="fa-solid fa-crown text-warning me-1"></i> Pelanggan Setia</strong>
                        <small class="text-muted d-block" style="font-size: 0.76rem;">Banyak promo menarik & dapat Credit Point di setiap menu</small>
                      </div>
                    </div>
                  </label>
                </div>
                <div class="col-sm-6">
                  <label :class="['p-3 border rounded h-100 d-block cursor-pointer', checkoutMode === 'guest' ? 'border-primary bg-primary-subtle shadow-sm' : 'bg-light']" style="cursor: pointer;">
                    <div class="d-flex align-items-center gap-2">
                      <input type="radio" value="guest" v-model="checkoutMode" name="checkoutMode">
                      <div>
                        <strong class="d-block text-dark"><i class="fa-solid fa-bag-shopping me-1"></i> Beli Makanan Saja</strong>
                        <small class="text-muted d-block" style="font-size: 0.76rem;">Pesan cepat tanpa perlu verifikasi email atau password</small>
                      </div>
                    </div>
                  </label>
                </div>
              </div>

              <!-- ========================================= -->
              <!-- KONDISI A: DAFTAR / LOGIN PELANGGAN SETIA -->
              <!-- ========================================= -->
              <div v-if="checkoutMode === 'loyalty'" class="p-3 rounded bg-warning-subtle border border-warning mb-3">
                <!-- If already logged in as loyalty member -->
                <div v-if="isCustomerLoggedIn">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                      <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-circle-check text-success fs-5"></i>
                        <span class="fw-bold text-dark fs-6">Masuk sebagai Pelanggan Setia: {{ customerConcat }}</span>
                      </div>
                      <small class="text-muted d-block mt-1">
                        <i class="fa-solid fa-coins text-warning me-1"></i> Saldo Credit Point Anda: <strong class="text-dark font-mono">{{ customerPoints }} Pts</strong>
                      </small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" @click="logoutCustomer">
                      <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Ganti Akun
                    </button>
                  </div>

                  <!-- Promo Pelanggan Setia Selector (If Store Has Active Promos) -->
                  <div v-if="availablePromos.length > 0" class="mt-3 pt-3 border-top">
                    <label class="form-label small fw-bold"><i class="fa-solid fa-tags text-primary me-1"></i> Pilih Promo Pelanggan Setia:</label>
                    
                    <div class="d-flex flex-column gap-2 mb-2">
                      <label class="p-2 border rounded bg-white d-flex align-items-center gap-2 cursor-pointer" style="cursor: pointer;">
                        <input type="radio" :value="null" v-model="selectedLoyaltyPromo" name="loyaltyPromo">
                        <span class="small">Tanpa Promo Tambahan</span>
                      </label>

                      <label v-for="promo in availablePromos" :key="promo.id" class="p-2 border rounded bg-white d-flex align-items-center gap-2 cursor-pointer" style="cursor: pointer;">
                        <input type="radio" :value="promo" v-model="selectedLoyaltyPromo" name="loyaltyPromo">
                        <div class="flex-grow-1">
                          <strong class="small d-block text-primary">{{ promo.title }}</strong>
                          <small class="text-muted d-block" style="font-size: 0.75rem;">{{ promo.description || 'Diskon spesial keanggotaan' }}</small>
                        </div>
                        <span class="badge bg-success font-mono">
                          {{ promo.promo_type === 'all_menu_percent' ? promo.discount_percent + '% OFF' : (promo.promo_type === 'min_purchase_discount' ? 'Potong Rp ' + formatPrice(promo.discount_amount) : 'Diskon Menu') }}
                        </span>
                      </label>
                    </div>

                    <!-- Benefit Choice Selector: Diskon Potongan VS Poin Lebih Banyak -->
                    <div v-if="selectedLoyaltyPromo && selectedLoyaltyPromo.allow_benefit_choice" class="p-2 rounded bg-light border mt-2">
                      <span class="small fw-bold d-block text-dark mb-1">
                        <i class="fa-solid fa-code-compare text-warning me-1"></i> Pilihan Keuntungan Promo:
                      </span>
                      <div class="d-flex gap-2">
                        <label class="p-2 rounded border bg-white flex-grow-1 cursor-pointer" style="cursor: pointer;">
                          <input type="radio" value="discount" v-model="loyaltyBenefitChoice">
                          <span class="small ms-1 fw-bold text-success">Pakai Potongan Diskon</span>
                        </label>
                        <label class="p-2 rounded border bg-white flex-grow-1 cursor-pointer" style="cursor: pointer;">
                          <input type="radio" value="points" v-model="loyaltyBenefitChoice">
                          <span class="small ms-1 fw-bold text-primary">Ambil Poin Lebih Banyak ({{ selectedLoyaltyPromo.extra_points_multiplier }}x Poin)</span>
                        </label>
                      </div>
                    </div>
                  </div>

                  <!-- Delivery address for logged in customer -->
                  <div class="mt-3 pt-3 border-top">
                    <label class="form-label small fw-bold">Alamat Pengiriman Pesanan <span class="text-danger">*</span></label>
                    <textarea v-model="customerForm.address" rows="2" class="form-input" placeholder="Jalan, No. Rumah, RT/RW, Patokan..." required></textarea>
                  </div>
                </div>

                <!-- If NOT logged in as loyalty member: Register or Login Tabs -->
                <div v-else>
                  <div class="d-flex gap-2 mb-3">
                    <button type="button" :class="['btn btn-sm flex-grow-1 fw-bold', loyaltyAuthTab === 'register' ? 'btn-primary' : 'btn-outline-secondary']" @click="loyaltyAuthTab = 'register'">
                      <i class="fa-solid fa-user-plus me-1"></i> Daftar Pelanggan Setia Baru
                    </button>
                    <button type="button" :class="['btn btn-sm flex-grow-1 fw-bold', loyaltyAuthTab === 'login' ? 'btn-primary' : 'btn-outline-secondary']" @click="loyaltyAuthTab = 'login'">
                      <i class="fa-solid fa-right-to-bracket me-1"></i> Sudah Punya Akun? Masuk
                    </button>
                  </div>

                  <!-- SUB-VIEW 1: REGISTER FORM -->
                  <div v-if="loyaltyAuthTab === 'register'">
                    <!-- If registration successfully submitted, show verification banner -->
                    <div v-if="registerSuccessMessage" class="p-3 rounded bg-success-subtle border border-success text-center">
                      <i class="fa-solid fa-envelope-circle-check text-success fa-2x mb-2"></i>
                      <h6 class="fw-bold text-success mb-1">Email Verifikasi Telah Dikirim!</h6>
                      <p class="small text-muted mb-3">
                        Kami telah mengirimkan link aktivasi ke <strong>{{ registerSuccessEmail }}</strong>. Selama proses aktivasi ini, <strong>pesanan di keranjang Anda tetap tersimpan aman</strong>!
                      </p>
                      <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <button type="button" class="btn btn-sm btn-success" @click="checkVerificationStatus" :disabled="checkingVerification">
                          <i v-if="checkingVerification" class="fa-solid fa-spinner fa-spin me-1"></i>
                          <i v-else class="fa-solid fa-rotate me-1"></i> Saya Sudah Verifikasi Email
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="resendActivationEmail" :disabled="resendingEmail">
                          <i v-if="resendingEmail" class="fa-solid fa-spinner fa-spin me-1"></i>
                          <i v-else class="fa-solid fa-paper-plane me-1"></i> Kirim Ulang Link
                        </button>
                      </div>
                    </div>

                    <!-- Register Inputs -->
                    <div v-else>
                      <div class="p-2 mb-3 rounded bg-white border small text-muted">
                        <i class="fa-solid fa-star text-warning me-1"></i>
                        Daftar sekali untuk kumpulkan credit points, dapat potongan harga diskon, dan promo spesial member!
                      </div>

                      <div class="row g-2 mb-2">
                        <div class="col-sm-7">
                          <label class="form-label small fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                          <input type="text" v-model="loyaltyRegisterForm.name" class="form-input" placeholder="Contoh: Muhammad Naufal" required />
                        </div>
                        <div class="col-sm-5">
                          <label class="form-label small fw-bold">Jenis Kelamin <span class="text-danger">*</span></label>
                          <div class="d-flex gap-2 pt-1">
                            <label class="small cursor-pointer"><input type="radio" value="Laki-laki" v-model="loyaltyRegisterForm.gender"> Laki-laki</label>
                            <label class="small cursor-pointer"><input type="radio" value="Perempuan" v-model="loyaltyRegisterForm.gender"> Perempuan</label>
                          </div>
                        </div>
                      </div>

                      <div class="row g-2 mb-2">
                        <div class="col-sm-6">
                          <label class="form-label small fw-bold">Email Valid <span class="text-danger">*</span></label>
                          <input type="email" v-model="loyaltyRegisterForm.email" class="form-input" placeholder="nama@gmail.com" required />
                        </div>
                        <div class="col-sm-6">
                          <label class="form-label small fw-bold">Password Login <span class="text-danger">*</span></label>
                          <input type="password" v-model="loyaltyRegisterForm.password" class="form-input" placeholder="Minimal 6 karakter" required />
                        </div>
                      </div>

                      <div class="row g-2 mb-2">
                        <div class="col-sm-6">
                          <label class="form-label small fw-bold">No. HP / WhatsApp <span class="text-danger">*</span></label>
                          <input type="text" v-model="loyaltyRegisterForm.whatsapp" class="form-input font-mono" placeholder="081298765432" required />
                        </div>
                        <div class="col-sm-6">
                          <label class="form-label small fw-bold">Pekerjaan Saat Ini <span class="text-danger">*</span></label>
                          <select v-model="loyaltyRegisterForm.occupation" class="form-select form-select-sm">
                            <option value="Karyawan Swasta">Karyawan Swasta</option>
                            <option value="PNS / BUMN">PNS / BUMN</option>
                            <option value="Wiraswasta / Pengusaha">Wiraswasta / Pengusaha</option>
                            <option value="Mahasiswa / Pelajar">Mahasiswa / Pelajar</option>
                            <option value="Ibu Rumah Tangga">Ibu Rumah Tangga</option>
                            <option value="Profesional / Freelancer">Profesional / Freelancer</option>
                            <option value="Lainnya">Lainnya</option>
                          </select>
                        </div>
                      </div>

                      <!-- Pilih Toko Langganan (Multi-Store Selection) -->
                      <div class="mb-3">
                        <label class="form-label small fw-bold">
                          <i class="fa-solid fa-store text-primary me-1"></i> Pilih Cabang Toko Langganan (Bisa pilih lebih dari satu):
                        </label>
                        <div class="p-2 rounded bg-white border" style="max-height: 120px; overflow-y: auto;">
                          <div v-for="st in allStoresList" :key="st.id" class="form-check">
                            <input class="form-check-input" type="checkbox" :value="st.id" :id="'store_cb_' + st.id" v-model="loyaltyRegisterForm.store_ids">
                            <label class="form-check-label small" :for="'store_cb_' + st.id">
                              <strong>{{ st.name }}</strong> ({{ st.code }}) - <span class="text-muted">{{ st.address }}</span>
                            </label>
                          </div>
                        </div>
                      </div>

                      <div class="mb-3">
                        <label class="form-label small fw-bold">Alamat Pengiriman Lengkap <span class="text-danger">*</span></label>
                        <textarea v-model="loyaltyRegisterForm.address" rows="2" class="form-input" placeholder="Jalan, No. Rumah, RT/RW, Patokan..." required></textarea>
                      </div>

                      <button type="button" class="btn btn-warning w-100 fw-bold py-2" @click="handleLoyaltyRegister" :disabled="registeringLoyalty">
                        <i v-if="registeringLoyalty" class="fa-solid fa-spinner fa-spin me-1"></i>
                        <i v-else class="fa-solid fa-paper-plane me-1"></i>
                        <span>{{ registeringLoyalty ? 'Mendaftarkan Akun...' : 'Daftar & Kirim Email Verifikasi' }}</span>
                      </button>
                    </div>
                  </div>

                  <!-- SUB-VIEW 2: LOGIN FORM -->
                  <div v-else>
                    <div class="p-2 mb-3 rounded bg-white border small text-muted">
                      Masukkan Email dan Password Anda yang telah terdaftar sebagai Pelanggan Setia.
                    </div>

                    <div class="mb-2">
                      <label class="form-label small fw-bold">Email Terdaftar <span class="text-danger">*</span></label>
                      <input type="email" v-model="loyaltyLoginForm.email" class="form-input" placeholder="nama@gmail.com" required />
                    </div>

                    <div class="mb-3">
                      <label class="form-label small fw-bold">Password Login <span class="text-danger">*</span></label>
                      <input type="password" v-model="loyaltyLoginForm.password" class="form-input" placeholder="Password Anda" required />
                    </div>

                    <button type="button" class="btn btn-primary w-100 fw-bold py-2" @click="handleLoyaltyLogin" :disabled="loggingInLoyalty">
                      <i v-if="loggingInLoyalty" class="fa-solid fa-spinner fa-spin me-1"></i>
                      <i v-else class="fa-solid fa-right-to-bracket me-1"></i>
                      <span>{{ loggingInLoyalty ? 'Memverifikasi...' : 'Masuk ke Akun Pelanggan Setia' }}</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- ============================================ -->
              <!-- KONDISI B: BELI MAKANAN SAJA (GUEST CHECKOUT)-->
              <!-- ============================================ -->
              <div v-else class="p-3 rounded bg-light border mb-3">
                <div class="p-2 mb-3 rounded bg-white border small text-muted">
                  <i class="fa-solid fa-circle-info text-primary me-1"></i>
                  Pemesanan cepat langsung selesai. Cukup lengkapi data kontak dan alamat pengiriman di bawah tanpa perlu verifikasi email.
                </div>

                <div class="mb-2">
                  <label class="form-label small fw-bold">Nama Lengkap Pemesan <span class="text-danger">*</span></label>
                  <input type="text" v-model="guestForm.name" class="form-input" placeholder="Contoh: Muhammad Naufal" required />
                </div>

                <div class="row g-2 mb-2">
                  <div class="col-sm-6">
                    <label class="form-label small fw-bold">Nomor HP / WhatsApp <span class="text-danger">*</span></label>
                    <input type="text" v-model="guestForm.whatsapp" class="form-input font-mono" placeholder="081298765432" required />
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label small fw-bold">Email Aktif <span class="text-danger">*</span></label>
                    <input type="email" v-model="guestForm.email" class="form-input" placeholder="nama@gmail.com" required />
                  </div>
                </div>

                <div class="mb-2">
                  <label class="form-label small fw-bold">Alamat Pengiriman Lengkap <span class="text-danger">*</span></label>
                  <textarea v-model="guestForm.address" rows="2" class="form-input" placeholder="Jalan, No. Rumah, RT/RW, Patokan..." required></textarea>
                </div>
              </div>

              <!-- 3. Payment Method Selector -->
              <div class="mb-4">
                <label class="form-label small fw-bold">Metode Pembayaran</label>
                <div class="d-flex flex-column gap-2">
                  <!-- Mayar.id Payment Gateway if activated -->
                  <label v-if="store.payment_type === 'payment_gateway'" class="option-radio-card">
                    <input type="radio" value="Payment Gateway (Mayar.id)" v-model="paymentMethod" name="paymentMethod" />
                    <div class="flex-grow-1 ms-2">
                      <div class="fw-bold"><i class="fa-solid fa-credit-card text-success me-1"></i> Payment Gateway (Mayar.id)</div>
                      <small class="text-muted d-block">QRIS Otomatis, Virtual Account Bank & E-Wallet (Lunas Instan)</small>
                    </div>
                    <span class="badge badge-primary">Online Otomatis</span>
                  </label>

                  <!-- QRIS Dinamis Toko -->
                  <label v-else class="option-radio-card">
                    <input type="radio" value="QRIS Dinamis" v-model="paymentMethod" name="paymentMethod" />
                    <div class="flex-grow-1 ms-2">
                      <div class="fw-bold"><i class="fa-solid fa-qrcode text-primary me-1"></i> QRIS Toko (Scan Mandiri)</div>
                      <small class="text-muted d-block">Scan QRIS menggunakan m-Banking atau E-Wallet apapun</small>
                    </div>
                    <span class="badge badge-primary">Rekomendasi</span>
                  </label>

                  <!-- COD option -->
                  <label class="option-radio-card">
                    <input type="radio" value="Bayar Terima Makanan" v-model="paymentMethod" name="paymentMethod" />
                    <div class="flex-grow-1 ms-2">
                      <div class="fw-bold"><i class="fa-solid fa-hand-holding-dollar text-success me-1"></i> Bayar Terima Makanan (COD)</div>
                      <small class="text-muted d-block">Bayar tunai kepada kurir saat pesanan tiba</small>
                    </div>
                  </label>
                </div>
              </div>

              <!-- 4. Order Summary Box -->
              <div class="p-3 rounded bg-light border mb-3">
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted small">Cabang Penyedia:</span>
                  <span class="fw-bold">{{ store.name }} ({{ store.code }})</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted small">Subtotal Menu:</span>
                  <span class="font-mono">Rp {{ formatPrice(cartTotalPrice) }}</span>
                </div>
                <div v-if="loyaltyPromoDiscount > 0" class="d-flex justify-content-between mb-1 text-success">
                  <span class="small"><i class="fa-solid fa-tag me-1"></i> Diskon Pelanggan Setia:</span>
                  <span class="font-mono fw-bold">- Rp {{ formatPrice(loyaltyPromoDiscount) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top mb-2">
                  <span class="text-muted small">Total Pembayaran:</span>
                  <strong class="fs-4 font-mono text-danger">Rp {{ formatPrice(cartFinalPrice) }}</strong>
                </div>

                <!-- Reward Points Estimation for Loyalty Customers -->
                <div v-if="checkoutMode === 'loyalty' && finalEarnedPoints > 0" class="p-2 rounded bg-warning-subtle border border-warning text-center">
                  <small class="text-dark fw-bold">
                    <i class="fa-solid fa-coins text-warning me-1"></i> Estimasi Credit Point Diperoleh: +{{ finalEarnedPoints }} Pts
                  </small>
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

          <div class="p-3 rounded bg-light border mb-2">
            <small class="text-muted d-block">Nomor Pesanan (Order ID):</small>
            <strong class="fs-4 font-mono text-danger">{{ createdOrderData.order_number }}</strong>
          </div>

          <!-- Reward Points Received callout -->
          <div v-if="createdOrderData.points_earned > 0" class="alert alert-warning py-2 px-3 mb-2 small fw-bold">
            <i class="fa-solid fa-coins text-warning me-1"></i> Selamat! Anda memperoleh +{{ createdOrderData.points_earned }} Credit Points ke akun Anda!
          </div>

          <!-- Status Pembayaran Badge -->
          <div class="d-flex justify-content-between align-items-center p-2 px-3 rounded bg-light border mb-3">
            <span class="small text-muted">Status Pembayaran:</span>
            <span v-if="createdOrderData.payment_status === 'paid'" class="badge bg-success font-mono fs-6 px-3 py-1">
              <i class="fa-solid fa-circle-check me-1"></i> Paid
            </span>
            <span v-else class="badge bg-warning text-dark font-mono fs-6 px-3 py-1">
              <i class="fa-solid fa-clock me-1"></i> Unpaid
            </span>
          </div>

          <!-- 1. If Paid -->
          <div v-if="createdOrderData.payment_status === 'paid'" class="p-3 border border-success rounded bg-success-subtle mb-3 text-start shadow-sm">
            <div class="d-flex align-items-center gap-2">
              <i class="fa-solid fa-circle-check text-success fs-3"></i>
              <div>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-success text-white font-mono">Paid</span>
                  <strong class="text-success">Pembayaran Lunas</strong>
                </div>
                <small class="text-muted d-block mt-1">Pembayaran telah terkonfirmasi dan pesanan segera disiapkan dapur.</small>
              </div>
            </div>
          </div>

          <!-- 2. If Payment Gateway (Mayar) Unpaid -->
          <div v-else-if="createdOrderData.payment_url" class="p-3 rounded bg-light border mb-3 text-start">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-bold"><i class="fa-solid fa-credit-card text-success me-1"></i> Mayar.id Gateway</span>
              <span class="badge bg-warning text-dark">Menunggu Pembayaran</span>
            </div>
            <p class="text-muted small mb-3">
              Jendela pembayaran otomatis terbuka. Jika tertutup atau terblokir pop-up browser, silakan klik tombol di bawah untuk membuka kembali invoice pembayaran:
            </p>
            <button class="btn btn-primary w-100 py-2 fw-bold" @click="openMayarPopup(createdOrderData.payment_url, createdOrderData.order_number)">
              <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Buka Pembayaran Mayar.id
            </button>
          </div>

          <!-- 3. If QRIS Dinamis Cabang (Static QRIS) -->
          <div v-else-if="createdOrderData.payment_method === 'QRIS Dinamis' || createdOrderData.payment_method === 'QRIS Statik'" class="mb-3">
            <div class="qris-card-wrapper p-3 border rounded bg-light text-center">
              <span class="badge bg-primary mb-2 font-mono">SCAN QRIS UNTUK MEMBAYAR</span>
              <div class="qris-image-container my-2">
                <img :src="dynamicQrisUrl" alt="QRIS Code" class="img-fluid border rounded p-2 bg-white shadow-sm" style="max-width: 220px;" />
              </div>
              <small class="text-muted d-block mb-3">
                Scan kode QRIS di atas menggunakan m-Banking (BCA, Mandiri, BRI, BNI) atau E-Wallet (GoPay, OVO, Dana, ShopeePay).
              </small>

              <!-- Upload Bukti Bayar QRIS -->
              <div class="border-top pt-3 text-start">
                <label class="form-label small fw-bold mb-1"><i class="fa-solid fa-receipt text-primary me-1"></i> Upload Bukti Transfer QRIS:</label>
                <input type="file" ref="modalFileInputRef" @change="handleModalFileChange" accept="image/jpeg,image/png,image/webp" class="d-none" />

                <div v-if="!modalProofPreview" class="d-grid gap-2">
                  <button type="button" class="btn btn-outline-primary btn-sm py-2" @click="triggerModalFileInput">
                    <i class="fa-solid fa-camera me-1"></i> Pilih Foto Bukti Pembayaran
                  </button>
                </div>

                <div v-else class="text-center">
                  <img :src="modalProofPreview" alt="Preview Bukti" class="img-fluid rounded border mb-2 shadow-sm" style="max-height: 140px;" />
                  <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1" @click="clearModalSelectedFile">Ganti Foto</button>
                    <button type="button" class="btn btn-success btn-sm flex-grow-1" @click="uploadModalProof" :disabled="modalUploading">
                      <i v-if="modalUploading" class="fa-solid fa-spinner fa-spin me-1"></i>
                      <i v-else class="fa-solid fa-cloud-arrow-up me-1"></i>
                      <span>{{ modalUploading ? 'Mengunggah...' : 'Kirim Bukti' }}</span>
                    </button>
                  </div>
                </div>
                <div v-if="modalUploadError" class="text-danger small mt-1">{{ modalUploadError }}</div>
              </div>
            </div>
          </div>

          <!-- 4. If COD -->
          <div v-else class="p-3 rounded bg-light border mb-3 text-start">
            <div class="d-flex align-items-center gap-2">
              <i class="fa-solid fa-hand-holding-dollar text-success fs-3"></i>
              <div>
                <strong>Bayar Tunai di Tempat (COD)</strong>
                <small class="text-muted d-block">Siapkan uang tunai sebesar <strong>Rp {{ formatPrice(createdOrderData.total_amount) }}</strong> saat pesanan tiba.</small>
              </div>
            </div>
          </div>

          <div class="d-flex flex-column gap-2">
            <Link :href="'/trace/' + createdOrderData.order_number" class="btn-primary py-2 text-decoration-none">
              <i class="fa-solid fa-location-crosshairs me-1"></i> Lacak Status Pesanan (Kitchen Tracking)
            </Link>
            <button class="btn-secondary py-2" @click="resetAndCloseSuccessModal">
              Tutup & Selesai
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
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
  },
  allStores: {
    type: Array,
    default: () => []
  },
  loyaltyPromos: {
    type: Array,
    default: () => []
  }
})

const allStoresList = computed(() => {
  if (props.allStores && props.allStores.length > 0) return props.allStores
  return [props.store, ...(props.otherStores || [])]
})

const availablePromos = computed(() => {
  return props.loyaltyPromos || []
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

// Cart State & Persistent Backup to LocalStorage (Order draft will never be lost!)
const cartItems = ref([])
const showCartModal = ref(false)

// Save cart to local storage whenever items change
watch(cartItems, (newVal) => {
  if (newVal && newVal.length > 0) {
    localStorage.setItem('naumil_customer_cart_backup', JSON.stringify(newVal))
    localStorage.setItem('naumil_last_store_slug', props.store.slug)
    localStorage.setItem('naumil_return_store_url', window.location.href)
  }
}, { deep: true })

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
    localStorage.removeItem('naumil_customer_cart_backup')
  }
}

const cartTotalQuantity = computed(() => {
  return cartItems.value.reduce((acc, it) => acc + it.qty, 0)
})

const cartTotalPrice = computed(() => {
  return cartItems.value.reduce((acc, it) => acc + it.subtotal, 0)
})

// ==========================================
// CHECKOUT MODE & CUSTOMER STATE
// ==========================================
const checkoutMode = ref('loyalty') // 'loyalty' or 'guest'
const loyaltyAuthTab = ref('register') // 'register' or 'login'

// Pelanggan Setia Registration Form
const loyaltyRegisterForm = ref({
  name: '',
  gender: 'Laki-laki',
  email: '',
  password: '',
  whatsapp: '',
  occupation: 'Karyawan Swasta',
  store_ids: [props.store.id],
  address: ''
})

// Pelanggan Setia Login Form
const loyaltyLoginForm = ref({
  email: '',
  password: ''
})

// Guest Checkout Form (Beli makanan saja)
const guestForm = ref({
  name: '',
  whatsapp: '',
  email: '',
  address: ''
})

// Active customer state
const customerForm = ref({
  name: '',
  whatsapp: '',
  email: '',
  address: ''
})

const paymentMethod = ref(props.store.payment_type === 'payment_gateway' ? 'Payment Gateway (Mayar.id)' : 'QRIS Dinamis')
const isCustomerLoggedIn = ref(false)
const customerConcat = ref('')
const customerPoints = ref(0)
const customerData = ref(null)

const registeringLoyalty = ref(false)
const registerSuccessMessage = ref('')
const registerSuccessEmail = ref('')
const loggingInLoyalty = ref(false)
const checkingVerification = ref(false)
const resendingEmail = ref(false)

// Loyalty Promo & Benefit Selection
const selectedLoyaltyPromo = ref(availablePromos.value.length > 0 ? availablePromos.value[0] : null)
const loyaltyBenefitChoice = ref('discount') // 'discount' or 'points'

// Calculate loyalty base points from menu reward_points
const baseEarnedPoints = computed(() => {
  return cartItems.value.reduce((acc, it) => {
    return acc + ((Number(it.menu?.reward_points) || 0) * it.qty)
  }, 0)
})

// Calculate loyalty promo discount amount
const loyaltyPromoDiscount = computed(() => {
  if (checkoutMode.value !== 'loyalty' || !isCustomerLoggedIn.value || !selectedLoyaltyPromo.value) {
    return 0
  }

  const promo = selectedLoyaltyPromo.value

  // If promo allows choice and customer picked 'points', discount is 0
  if (promo.allow_benefit_choice && loyaltyBenefitChoice.value === 'points') {
    return 0
  }

  if (promo.promo_type === 'all_menu_percent') {
    return (cartTotalPrice.value * (Number(promo.discount_percent) || 0)) / 100
  }

  if (promo.promo_type === 'min_purchase_discount') {
    if (cartTotalPrice.value >= (Number(promo.min_purchase_amount) || 0)) {
      return Number(promo.discount_amount) || 0
    }
    return 0
  }

  if (promo.promo_type === 'per_menu') {
    let disc = 0
    cartItems.value.forEach(it => {
      const pMenu = (promo.menus || []).find(m => m.id === it.menu.id)
      if (pMenu && pMenu.pivot) {
        if (Number(pMenu.pivot.discount_percent) > 0) {
          disc += (it.subtotal * Number(pMenu.pivot.discount_percent)) / 100
        } else if (Number(pMenu.pivot.discount_amount) > 0) {
          disc += (Number(pMenu.pivot.discount_amount) * it.qty)
        }
      }
    })
    return disc
  }

  return 0
})

// Calculate effective reward points
const finalEarnedPoints = computed(() => {
  if (checkoutMode.value !== 'loyalty' || !isCustomerLoggedIn.value) {
    return 0
  }

  let pts = baseEarnedPoints.value

  if (selectedLoyaltyPromo.value && selectedLoyaltyPromo.value.allow_benefit_choice && loyaltyBenefitChoice.value === 'points') {
    const promo = selectedLoyaltyPromo.value
    const mult = Math.max(1, Number(promo.extra_points_multiplier) || 2)
    const bonus = Number(promo.extra_points_bonus) || 0
    pts = Math.round((pts * mult) + bonus)
  }

  return pts
})

// Final Cart Price
const cartFinalPrice = computed(() => {
  return Math.max(0, cartTotalPrice.value - loyaltyPromoDiscount.value)
})

// Quick open modal for loyalty
const openLoyaltyModal = () => {
  checkoutMode.value = 'loyalty'
  showCartModal.value = true
}

// Load session & restore draft cart
const loadCustomerSession = async () => {
  const cached = localStorage.getItem('naumil_customer_session')
  if (cached) {
    try {
      const client = JSON.parse(cached)
      if (client.email) {
        applyClientData(client)
      }
    } catch (e) {
      localStorage.removeItem('naumil_customer_session')
    }
  }

  try {
    const res = await axios.get('/api/v1/customer/current-session')
    if (res.data?.authenticated && res.data?.client) {
      applyClientData(res.data.client)
    }
  } catch (e) {}
}

const applyClientData = (client) => {
  customerData.value = client
  customerForm.value.email = client.email || ''
  customerForm.value.name = client.name || ''
  customerForm.value.whatsapp = client.whatsapp || ''
  customerForm.value.address = client.address || ''
  customerPoints.value = client.loyalty_points || 0
  isCustomerLoggedIn.value = true
  customerConcat.value = `${client.name} (${client.email})`
  localStorage.setItem('naumil_customer_session', JSON.stringify(client))
}

// Register as loyalty customer
const handleLoyaltyRegister = async () => {
  if (!loyaltyRegisterForm.value.name || !loyaltyRegisterForm.value.email || !loyaltyRegisterForm.value.password || !loyaltyRegisterForm.value.whatsapp) {
    alert('Mohon lengkapi Nama Lengkap, Email Valid, Password, dan Nomor WhatsApp.')
    return
  }

  try {
    registeringLoyalty.value = true

    // Backup cart to local storage so draft is never lost!
    localStorage.setItem('naumil_customer_cart_backup', JSON.stringify(cartItems.value))
    localStorage.setItem('naumil_last_store_slug', props.store.slug)
    localStorage.setItem('naumil_return_store_url', window.location.href)

    const res = await axios.post('/api/v1/customer/register-loyalty', {
      ...loyaltyRegisterForm.value,
      store_ids: loyaltyRegisterForm.value.store_ids
    })

    if (res.data.success) {
      registerSuccessEmail.value = loyaltyRegisterForm.value.email
      registerSuccessMessage.value = res.data.message
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal mendaftar Pelanggan Setia.')
  } finally {
    registeringLoyalty.value = false
  }
}

// Login as loyalty customer
const handleLoyaltyLogin = async () => {
  if (!loyaltyLoginForm.value.email || !loyaltyLoginForm.value.password) {
    alert('Mohon masukkan Email dan Password.')
    return
  }

  try {
    loggingInLoyalty.value = true
    const res = await axios.post('/api/v1/customer/login', loyaltyLoginForm.value)
    if (res.data.success && res.data.client) {
      applyClientData(res.data.client)
      registerSuccessMessage.value = ''
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Login gagal. Periksa kembali email dan password Anda.')
  } finally {
    loggingInLoyalty.value = false
  }
}

// Check verification status button
const checkVerificationStatus = async () => {
  checkingVerification.value = true
  try {
    const res = await axios.get('/api/v1/customer/current-session')
    if (res.data?.authenticated && res.data?.client) {
      applyClientData(res.data.client)
      registerSuccessMessage.value = ''
      alert('Selamat! Akun Pelanggan Setia Anda telah terverifikasi. Silakan lanjutkan pesanan Anda.')
    } else {
      alert('Akun belum terdeteksi aktif. Silakan pastikan Anda telah mengklik link verifikasi di kotak masuk email Anda.')
    }
  } catch (e) {
    alert('Gagal mengecek status verifikasi.')
  } finally {
    checkingVerification.value = false
  }
}

// Resend activation email
const resendActivationEmail = async () => {
  const email = registerSuccessEmail.value || loyaltyLoginForm.value.email || customerForm.value.email
  if (!email) {
    alert('Mohon masukkan email Anda terlebih dahulu.')
    return
  }
  try {
    resendingEmail.value = true
    const res = await axios.post('/api/v1/customer/resend-activation', { email })
    alert(res.data.message || 'Email verifikasi baru berhasil dikirimkan.')
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal mengirim ulang email.')
  } finally {
    resendingEmail.value = false
  }
}

const logoutCustomer = async () => {
  localStorage.removeItem('naumil_customer_session')
  isCustomerLoggedIn.value = false
  customerConcat.value = ''
  customerPoints.value = 0
  customerData.value = null
  customerForm.value.email = ''
  customerForm.value.name = ''
  customerForm.value.whatsapp = ''
  customerForm.value.address = ''
  registerSuccessMessage.value = ''

  try {
    await axios.post('/api/v1/customer/logout')
  } catch (e) {}
}

onMounted(() => {
  loadCustomerSession()

  // Restore cart draft from localStorage if cart is empty
  const savedCart = localStorage.getItem('naumil_customer_cart_backup')
  if ((!cartItems.value || cartItems.value.length === 0) && savedCart) {
    try {
      const parsed = JSON.parse(savedCart)
      if (Array.isArray(parsed) && parsed.length > 0) {
        cartItems.value = parsed
      }
    } catch (e) {}
  }
})

// Mayar.id Popup Window & Auto-close Polling
let mayarPopup = null
let paymentPollingInterval = null
let messageListener = null

const openMayarPopup = (paymentUrl, orderNumber) => {
  if (!paymentUrl) return
  const w = 480
  const h = 720
  const left = window.screenX + (window.outerWidth - w) / 2
  const top = window.screenY + (window.outerHeight - h) / 2

  mayarPopup = window.open(
    paymentUrl,
    'MayarPaymentPopup',
    `width=${w},height=${h},top=${top},left=${left},resizable=yes,scrollbars=yes,status=no`
  )

  startPaymentMonitoring(orderNumber)
}

const startPaymentMonitoring = (orderNumber) => {
  stopPaymentMonitoring()

  messageListener = (event) => {
    if (event.data?.type === 'MAYAR_PAYMENT_SUCCESS' && (!event.data?.orderNumber || event.data?.orderNumber === orderNumber)) {
      onPaymentSuccess(orderNumber)
    }
  }
  window.addEventListener('message', messageListener)

  paymentPollingInterval = setInterval(async () => {
    try {
      const res = await axios.get(`/api/v1/orders/trace/${orderNumber}`)
      if (res.data?.success && res.data?.data?.order) {
        const order = res.data.data.order
        if (order.payment_status === 'paid' || res.data.data.current_stage_index > 0) {
          onPaymentSuccess(orderNumber)
        }
      }
    } catch (e) {
      console.error('Payment polling error', e)
    }
  }, 2500)
}

const onPaymentSuccess = (orderNumber) => {
  stopPaymentMonitoring()
  if (mayarPopup && !mayarPopup.closed) {
    try {
      mayarPopup.close()
    } catch (e) {}
  }
  router.visit('/trace/' + orderNumber)
}

const stopPaymentMonitoring = () => {
  if (paymentPollingInterval) {
    clearInterval(paymentPollingInterval)
    paymentPollingInterval = null
  }
  if (messageListener) {
    window.removeEventListener('message', messageListener)
    messageListener = null
  }
}

onUnmounted(() => {
  stopPaymentMonitoring()
})

const submittingOrder = ref(false)
const createdOrderData = ref(null)

const submitOrder = async () => {
  if (cartItems.value.length === 0) {
    alert('Keranjang belanja Anda masih kosong.')
    return
  }

  let finalName = ''
  let finalEmail = ''
  let finalWa = ''
  let finalAddress = ''

  if (checkoutMode.value === 'loyalty') {
    if (!isCustomerLoggedIn.value) {
      alert('Untuk memesan sebagai Pelanggan Setia, silakan selesaikan pendaftaran akun atau login terlebih dahulu.')
      return
    }
    finalName = customerForm.value.name
    finalEmail = customerForm.value.email
    finalWa = customerForm.value.whatsapp
    finalAddress = customerForm.value.address
  } else {
    // Guest checkout (Beli makanan saja)
    finalName = guestForm.value.name
    finalEmail = guestForm.value.email
    finalWa = guestForm.value.whatsapp
    finalAddress = guestForm.value.address
  }

  if (!finalName || !finalEmail || !finalWa || !finalAddress) {
    alert('Mohon lengkapi Nama Lengkap, Nomor HP / WhatsApp, Email Aktif, dan Alamat Pengiriman.')
    return
  }

  try {
    submittingOrder.value = true

    const payload = {
      store_id: props.store.id,
      campaign_id: props.campaigns && props.campaigns.length > 0 ? props.campaigns[0].id : null,
      order_type: checkoutMode.value,
      loyalty_promo_id: checkoutMode.value === 'loyalty' && selectedLoyaltyPromo.value ? selectedLoyaltyPromo.value.id : null,
      loyalty_benefit_choice: checkoutMode.value === 'loyalty' ? loyaltyBenefitChoice.value : null,
      customer_name: finalName,
      customer_email: finalEmail,
      customer_whatsapp: finalWa,
      customer_address: finalAddress,
      payment_method: paymentMethod.value,
      notes: 'Order via Storefront ' + props.store.name + (checkoutMode.value === 'loyalty' ? ' (Pelanggan Setia)' : ' (Tamu)'),
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
      localStorage.removeItem('naumil_customer_cart_backup')
      showCartModal.value = false

      if (res.data.data.client) {
        applyClientData(res.data.data.client)
      }

      if (res.data.data.payment_url) {
        setTimeout(() => {
          openMayarPopup(res.data.data.payment_url, res.data.data.order_number)
        }, 300)
      }
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

const modalSelectedFile = ref(null)
const modalProofPreview = ref(null)
const modalFileInputRef = ref(null)
const modalUploading = ref(false)
const modalUploadError = ref('')

const triggerModalFileInput = () => {
  if (modalFileInputRef.value) modalFileInputRef.value.click()
}

const handleModalFileChange = (e) => {
  modalUploadError.value = ''
  const file = e.target.files?.[0]
  if (!file) return

  const allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']
  if (!allowed.includes(file.type)) {
    modalUploadError.value = 'Format file tidak didukung. Mohon upload gambar JPG, PNG, atau WEBP.'
    return
  }

  if (file.size > 5 * 1024 * 1024) {
    modalUploadError.value = 'Ukuran file melebihi batas 5MB.'
    return
  }

  modalSelectedFile.value = file
  modalProofPreview.value = URL.createObjectURL(file)
}

const clearModalSelectedFile = () => {
  modalSelectedFile.value = null
  if (modalProofPreview.value) {
    URL.revokeObjectURL(modalProofPreview.value)
    modalProofPreview.value = null
  }
  if (modalFileInputRef.value) {
    modalFileInputRef.value.value = ''
  }
}

const uploadModalProof = async () => {
  if (!modalSelectedFile.value || !createdOrderData.value) return
  try {
    modalUploading.value = true
    modalUploadError.value = ''

    const formData = new FormData()
    formData.append('proof_image', modalSelectedFile.value)

    const orderNum = createdOrderData.value.order_number
    const res = await axios.post(`/api/v1/orders/${orderNum}/upload-proof`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    if (res.data.success) {
      createdOrderData.value.payment_status = 'paid'
      createdOrderData.value.payment_proof = res.data.data.order?.payment_proof
      createdOrderData.value.payment_proof_url = res.data.data.payment_proof_url
      clearModalSelectedFile()
    } else {
      modalUploadError.value = res.data.message || 'Gagal mengunggah bukti QRIS.'
    }
  } catch (err) {
    modalUploadError.value = err.response?.data?.message || 'Terjadi kesalahan saat mengunggah bukti pembayaran.'
  } finally {
    modalUploading.value = false
  }
}

const resetAndCloseSuccessModal = () => {
  createdOrderData.value = null
  clearModalSelectedFile()
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

.menu-points-badge {
  position: absolute;
  bottom: 10px;
  left: 10px;
  font-size: 0.72rem;
  font-weight: 800;
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
