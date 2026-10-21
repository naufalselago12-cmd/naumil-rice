<template>
  <AppLayout>
    <div class="campaign-page">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-custom"></div>
        <p class="text-muted mt-3">Memuat informasi Open PO...</p>
      </div>

      <!-- Error / Not Found State -->
      <div v-else-if="error" class="container py-5 text-center">
        <div class="card p-5 max-w-500 mx-auto">
          <i class="fa-solid fa-triangle-exclamation text-danger fa-3x mb-3"></i>
          <h3 class="fw-bold mb-2">Campaign Tidak Ditemukan</h3>
          <p class="text-muted mb-4">{{ errorMessage }}</p>
          <Link href="/" class="btn btn-primary mx-auto">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda
          </Link>
        </div>
      </div>

      <!-- Main Campaign Content -->
      <div v-else class="campaign-content">
        <!-- Store & Campaign Hero Header -->
        <section class="campaign-header">
          <div class="container">
            <!-- Store Profile Bar -->
            <div class="store-badge-bar">
              <div class="store-info">
                <img :src="store.logo || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=100&q=80'" 
                     :alt="store.name" class="store-avatar" />
                <div>
                  <span class="badge badge-dark mb-1 font-mono">{{ store.code }}</span>
                  <h3 class="store-name mb-0">{{ store.name }}</h3>
                  <small class="text-muted"><i class="fa-solid fa-location-dot me-1 text-danger"></i> {{ store.address }}</small>
                </div>
              </div>

              <div class="store-actions d-flex align-items-center gap-2">
                <!-- Elegant Campaign Header Cart Button with Badge -->
                <button type="button" class="btn-store-cart shadow-sm" @click="showCheckoutModal = true" title="Buka Ringkasan Keranjang Pre-Order">
                  <div class="store-cart-icon-wrapper">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span v-if="totalCartQty > 0" class="store-cart-badge font-mono animate-bounce-subtle">
                      {{ totalCartQty }}
                    </span>
                  </div>
                  <div class="store-cart-content">
                    <span class="store-cart-title">Keranjang PO</span>
                    <span class="store-cart-sub font-mono">
                      {{ totalCartQty > 0 ? 'Rp ' + formatNumber(finalPayableTotal) : '0 Menu' }}
                    </span>
                  </div>
                </button>

                <a v-if="store.phone" :href="'https://wa.me/' + cleanPhone(store.phone)" target="_blank" class="btn-wa-sm">
                  <i class="fa-brands fa-whatsapp text-success fs-5"></i> Hubungi Toko
                </a>
              </div>
            </div>

            <!-- Campaign Banner & Expiry Status -->
            <div class="campaign-banner-card card mt-4">
              <div class="banner-img-wrapper">
                <img :src="campaign.banner_image || 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200&q=80'" 
                     :alt="campaign.title" class="banner-img" />
                <div class="banner-gradient"></div>
                
                <div class="banner-text">
                  <span :class="['badge', isExpired ? 'badge-red' : 'badge-green', 'mb-2']">
                    <i :class="isExpired ? 'fa-solid fa-ban' : 'fa-solid fa-circle-check'"></i>
                    {{ isExpired ? 'CLOSE ORDER (EXPIRED)' : 'OPEN PRE-ORDER AKTIF' }}
                  </span>
                  <h1 class="campaign-headline">{{ campaign.title }}</h1>
                  <p class="campaign-lead">{{ campaign.description }}</p>
                </div>
              </div>

              <!-- Schedule & Countdowns Bar -->
              <div class="schedule-bar">
                <div class="schedule-item">
                  <i class="fa-solid fa-hourglass-end text-danger fs-4"></i>
                  <div>
                    <span class="schedule-label">Batas Close Order:</span>
                    <strong class="schedule-value">{{ formatDate(campaign.close_order_at) }}</strong>
                    <small v-if="!isExpired" class="text-danger d-block fw-bold countdown-text">
                      <i class="fa-solid fa-clock me-1"></i> {{ countdownText }}
                    </small>
                    <small v-else class="text-danger d-block fw-bold">Waktu pemesanan telah ditutup</small>
                  </div>
                </div>

                <div class="schedule-divider"></div>

                <div class="schedule-item">
                  <i class="fa-solid fa-truck-fast text-success fs-4"></i>
                  <div>
                    <span class="schedule-label">Estimasi Makanan Ready:</span>
                    <strong class="schedule-value">{{ formatDate(campaign.ready_at) }}</strong>
                    <small class="text-success d-block fw-semibold">Disiapkan fresh oleh dapur cabang</small>
                  </div>
                </div>

                <div class="schedule-divider" v-if="campaign.max_quota"></div>

                <div class="schedule-item" v-if="campaign.max_quota">
                  <i class="fa-solid fa-boxes-stacked text-primary fs-4"></i>
                  <div>
                    <span class="schedule-label">Sisa Kuota Batch:</span>
                    <strong :class="['schedule-value', isSoldOut ? 'text-danger' : 'text-primary']">
                      {{ isSoldOut ? 'Habis (Sold Out)' : campaign.remaining_quota + ' / ' + campaign.max_quota + ' Porsi' }}
                    </strong>
                    <small class="text-muted d-block font-mono">Terpesan: {{ campaign.total_ordered }} porsi</small>
                  </div>
                </div>

                <div class="schedule-divider" v-if="campaign.discount_type !== 'none'"></div>

                <div class="schedule-item" v-if="campaign.discount_type !== 'none'">
                  <i class="fa-solid fa-tags text-warning fs-4"></i>
                  <div>
                    <span class="schedule-label">Promo Campaign:</span>
                    <strong class="schedule-value text-success">
                      <template v-if="campaign.discount_type === 'min_qty'">
                        Diskon Min. {{ campaign.min_order_qty || 15 }} Porsi: Diskon {{ campaign.discount_value }}% (atau Diskon Menu)
                      </template>
                      <template v-else-if="campaign.discount_type === 'fixed'">
                        Potongan Rp {{ formatNumber(campaign.discount_value) }}
                      </template>
                      <template v-else-if="campaign.discount_type === 'percentage'">
                        Diskon {{ campaign.discount_value }}%
                      </template>
                    </strong>
                    <small class="text-muted d-block">
                      <template v-if="campaign.discount_type === 'min_qty'">
                        Otomatis aktif saat total pesanan mencapai {{ campaign.min_order_qty || 15 }} porsi
                      </template>
                      <template v-else>
                        Otomatis terpotong di ringkasan
                      </template>
                    </small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Alert if Sold Out -->
            <div v-if="isSoldOut && !isExpired" class="alert-expired card mt-4 border-danger">
              <div class="d-flex align-items-center gap-3">
                <i class="fa-solid fa-boxes-stacked text-danger fs-2"></i>
                <div>
                  <h4 class="fw-bold mb-1 text-danger">Seluruh Kuota Pre-Order Telah Habis (Sold Out)</h4>
                  <p class="mb-0 text-muted">
                    Maksimum kuota pemesanan untuk batch Open PO ini telah terpenuhi. Terima kasih atas antusiasme Anda! Nantikan batch berikutnya.
                  </p>
                </div>
              </div>
            </div>

            <!-- Alert if Expired -->
            <div v-if="isExpired" class="alert-expired card mt-4">
              <div class="d-flex align-items-center gap-3">
                <i class="fa-solid fa-circle-exclamation text-danger fs-2"></i>
                <div>
                  <h4 class="fw-bold mb-1 text-danger">Masa Pre-Order Telah Berakhir (Close Order)</h4>
                  <p class="mb-0 text-muted">
                    QR Code dan link pre-order untuk batch ini sudah tidak menerima pesanan baru karena dapur sedang mempersiapkan bahan baku. Nantikan pembukaan batch PO berikutnya!
                  </p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Menus Section -->
        <section class="menus-section container mt-5">
          <!-- Category Filter Tabs -->
          <div class="category-tabs mb-4">
            <button :class="['tab-btn', activeCategory === 'all' ? 'active' : '']" 
                    @click="activeCategory = 'all'">
              Semua Menu ({{ menus.length }})
            </button>
            <button v-for="cat in uniqueCategories" :key="cat"
                    :class="['tab-btn', activeCategory === cat ? 'active' : '']"
                    @click="activeCategory = cat">
              {{ cat }}
            </button>
          </div>

          <!-- Menu Cards Grid -->
          <div class="menu-grid">
            <div v-for="menu in filteredMenus" :key="menu.id" class="menu-card card">
              <div class="menu-image-box">
                <img :src="menu.image || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=80'" 
                     :alt="menu.name" class="menu-img" />
                
                <div v-if="campaign.discount_type === 'min_qty'" class="discount-badge">
                  <template v-if="menu.discount_price > 0">Diskon Rp {{ formatNumber(menu.discount_price) }} (Min. {{ campaign.min_order_qty || 15 }} Porsi)</template>
                  <template v-else-if="campaign.discount_value > 0">Diskon {{ campaign.discount_value }}% (Min. {{ campaign.min_order_qty || 15 }} Porsi)</template>
                </div>
                <div v-else-if="menu.discount_price > 0" class="discount-badge">
                  HEMAT Rp {{ formatNumber(menu.discount_price) }}
                </div>

                <div v-if="menu.is_sold_out || isSoldOut || (menu.remaining_quota !== null && menu.remaining_quota <= 0)" class="stock-badge low">
                  Kuota Habis
                </div>
                <div v-else-if="menu.max_quota !== null" class="stock-badge" :class="menu.remaining_quota < 5 ? 'low' : ''">
                  Sisa Kuota: {{ menu.remaining_quota }} Porsi
                </div>
                <div v-else class="stock-badge">
                  PO Tersedia
                </div>
              </div>

              <div class="menu-card-body">
                <div class="category-tag">{{ menu.category_name }}</div>
                <h3 class="menu-name">{{ menu.name }}</h3>
                <p class="menu-desc">{{ menu.description }}</p>

                <div class="menu-pricing">
                  <div>
                    <span v-if="campaign.discount_type !== 'min_qty' && menu.discount_price > 0" class="old-price">
                      Rp {{ formatNumber(menu.custom_price || menu.original_price) }}
                    </span>
                    <div class="final-price">
                      Rp {{ formatNumber(menu.final_price) }}
                    </div>
                  </div>

                  <!-- Add Button -->
                  <button :disabled="isExpired || isSoldOut || menu.is_sold_out || (menu.remaining_quota !== null && menu.remaining_quota <= 0)" 
                          class="btn btn-primary btn-add" 
                          @click="openOptionModal(menu)">
                    <template v-if="isExpired">Ditutup</template>
                    <template v-else-if="isSoldOut || menu.is_sold_out || (menu.remaining_quota !== null && menu.remaining_quota <= 0)">Habis</template>
                    <template v-else>
                      <i class="fa-solid fa-plus"></i> Pesan
                    </template>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- ================= MODAL 1: CUSTOMIZE OPTIONS (TOPPING, UPSIZE, NOTES) ================= -->
      <div v-if="activeMenuModal" class="modal-overlay" @click.self="activeMenuModal = null">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="fw-bold fs-5 mb-0">Pilih Opsi & Kustomisasi</h3>
            <button class="close-btn" @click="activeMenuModal = null">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="p-4">
            <div class="d-flex gap-3 mb-4">
              <img :src="activeMenuModal.image" :alt="activeMenuModal.name" class="modal-thumb" />
              <div>
                <h4 class="fw-bold fs-6 mb-1">{{ activeMenuModal.name }}</h4>
                <div class="text-primary fw-bold fs-5">Rp {{ formatNumber(activeMenuModal.final_price) }}</div>
              </div>
            </div>

            <!-- Upsize Variant -->
            <div v-if="activeMenuModal.options?.upsizes?.length" class="option-group mb-4">
              <label class="option-title">
                <i class="fa-solid fa-arrows-up-to-line text-primary me-1"></i> Pilihan Porsi (Upsize)
              </label>
              <div class="option-list">
                <label v-for="up in activeMenuModal.options.upsizes" :key="up.id" class="option-radio-label">
                  <input type="radio" :value="up" v-model="selectedUpsize" />
                  <span class="flex-grow-1">{{ up.name }}</span>
                  <span class="fw-bold text-success">+ Rp {{ formatNumber(up.price) }}</span>
                </label>
              </div>
            </div>

            <!-- Toppings Checkboxes -->
            <div v-if="activeMenuModal.options?.toppings?.length" class="option-group mb-4">
              <label class="option-title">
                <i class="fa-solid fa-egg text-warning me-1"></i> Tambahan Topping Lezat
              </label>
              <div class="option-list">
                <label v-for="top in activeMenuModal.options.toppings" :key="top.id" class="option-checkbox-label">
                  <input type="checkbox" :value="top" v-model="selectedToppings" />
                  <span class="flex-grow-1">{{ top.name }}</span>
                  <span class="fw-bold text-success">+ Rp {{ formatNumber(top.price) }}</span>
                </label>
              </div>
            </div>

            <!-- Conditions / Levels -->
            <div v-if="activeMenuModal.options?.conditions?.length" class="option-group mb-4">
              <label class="option-title">
                <i class="fa-solid fa-pepper-hot text-danger me-1"></i> Tingkat Pedas / Kondisi
              </label>
              <div class="option-list">
                <label v-for="cond in activeMenuModal.options.conditions" :key="cond.id" class="option-radio-label">
                  <input type="radio" :value="cond" v-model="selectedCondition" />
                  <span class="flex-grow-1">{{ cond.name }}</span>
                  <span class="fw-bold text-success">+ Rp {{ formatNumber(cond.price) }}</span>
                </label>
              </div>
            </div>

            <!-- Special Note Input -->
            <div class="form-group mb-4">
              <label class="form-label">
                <i class="fa-solid fa-note-sticky text-warning me-1"></i> Catatan Khusus untuk Dapur (Opsional)
              </label>
              <input type="text" v-model="modalSpecialNotes" class="form-input" 
                     placeholder="Contoh: Pisahkan sambal, nasi daun jeruk agak banyakan" />
            </div>

            <!-- Quantity Selector -->
            <div class="qty-selector-row">
              <div>
                <span class="fw-bold d-block">Jumlah Porsi:</span>
                <small v-if="modalMaxQty !== null" class="text-muted" style="font-size: 0.78rem;">
                  Sisa kuota: {{ modalMaxQty }} porsi
                </small>
              </div>
              <div class="qty-control">
                <button class="qty-btn" @click="decrementModalQty">-</button>
                <span class="qty-num">{{ modalQty }}</span>
                <button class="qty-btn" :disabled="modalMaxQty !== null && modalQty >= modalMaxQty" 
                        @click="incrementModalQty">+</button>
              </div>
            </div>
          </div>

          <!-- Modal Footer with Calculated Total -->
          <div class="modal-footer p-4 border-top d-flex justify-content-between align-items-center">
            <div>
              <small class="text-muted d-block">Subtotal Item:</small>
              <div class="fs-5 fw-bold text-primary">Rp {{ formatNumber(modalItemSubtotal) }}</div>
            </div>
            <button class="btn btn-primary" @click="addToCartFromModal">
              <i class="fa-solid fa-cart-plus me-1"></i> Tambahkan ke Pesanan
            </button>
          </div>
        </div>
      </div>

      <!-- ================= FLOATING CART BAR ================= -->
      <div v-if="cart.length > 0 && !isExpired" class="floating-cart-bar">
        <div class="container d-flex justify-content-between align-items-center">
          <div class="cart-bar-info">
            <div class="cart-pill">
              <i class="fa-solid fa-bag-shopping"></i> {{ totalCartQty }} Menu
            </div>
            <div class="cart-bar-total">
              <small class="d-block text-white-50">Estimasi Total:</small>
              <span class="fw-bold fs-5 text-white">Rp {{ formatNumber(cartSubtotal) }}</span>
            </div>
          </div>
          <button class="btn-checkout" @click="showCheckoutModal = true">
            Lanjut ke Pembayaran <i class="fa-solid fa-arrow-right ms-1"></i>
          </button>
        </div>
      </div>

      <!-- ================= MODAL 2: CHECKOUT & USER IDENTITY & DYNAMIC QRIS ================= -->
      <div v-if="showCheckoutModal" class="modal-overlay" @click.self="showCheckoutModal = false">
        <div class="modal-content modal-checkout">
          <div class="modal-header">
            <h3 class="fw-bold fs-5 mb-0">Konfirmasi Pre-Order & Pembayaran</h3>
            <button class="close-btn" @click="showCheckoutModal = false">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="p-4">
            <!-- Pilihan Metode Pemesanan: Pelanggan Setia vs Beli Makanan Saja -->
            <div class="checkout-method-selector mb-4">
              <h5 class="section-subtitle mb-2">
                <i class="fa-solid fa-user-check text-primary me-2"></i> Pilih Metode Pemesanan
              </h5>
              <div class="row g-2 mb-3">
                <div class="col-sm-6">
                  <label :class="['p-3 border rounded h-100 d-block cursor-pointer', checkoutMode === 'loyalty' ? 'border-primary bg-primary-subtle shadow-sm' : 'bg-light']" style="cursor: pointer;">
                    <div class="d-flex align-items-center gap-2">
                      <input type="radio" value="loyalty" v-model="checkoutMode" name="checkoutMode">
                      <div>
                        <strong class="d-block text-primary"><i class="fa-solid fa-crown text-warning me-1"></i> Pelanggan Setia</strong>
                        <small class="text-muted d-block" style="font-size: 0.76rem;">Banyak promo menarik & kumpulkan reward point</small>
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
                        <small class="text-muted d-block" style="font-size: 0.76rem;">Pesan cepat tanpa perlu verifikasi email</small>
                      </div>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Option A: Pelanggan Setia -->
              <div v-if="checkoutMode === 'loyalty'" class="p-3 rounded bg-warning-subtle border border-warning mb-3">
                <!-- Logged-in State -->
                <div v-if="isReturningUser">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                      <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-circle-check text-success fs-5"></i>
                        <span class="fw-bold text-dark fs-6">Masuk sebagai: {{ clientConcat }}</span>
                      </div>
                      <small class="text-muted d-block mt-1">
                        <i class="fa-solid fa-coins text-warning me-1"></i> Saldo Credit Point: <strong class="text-dark font-mono">{{ customerLoyaltyPoints }} Pts</strong>
                      </small>
                    </div>
                    <button type="button" class="btn-text-sm" @click="clearReturningUser">Ganti Akun</button>
                  </div>

                  <div class="mt-3 pt-3 border-top">
                    <label class="form-label small fw-bold">Alamat Pengiriman Lengkap <span class="text-danger">*</span></label>
                    <textarea v-model="clientForm.address" rows="2" class="form-textarea" placeholder="Jalan, nomor rumah, RT/RW, patokan..." required></textarea>
                  </div>
                </div>

                <!-- Not Logged In: Register or Login Tabs -->
                <div v-else>
                  <div class="d-flex gap-2 mb-3">
                    <button type="button" :class="['btn btn-sm flex-grow-1 fw-bold', loyaltyTab === 'register' ? 'btn-primary' : 'btn-outline-secondary']" @click="loyaltyTab = 'register'">
                      <i class="fa-solid fa-user-plus me-1"></i> Daftar Pelanggan Setia
                    </button>
                    <button type="button" :class="['btn btn-sm flex-grow-1 fw-bold', loyaltyTab === 'login' ? 'btn-primary' : 'btn-outline-secondary']" @click="loyaltyTab = 'login'">
                      <i class="fa-solid fa-right-to-bracket me-1"></i> Masuk Akun
                    </button>
                  </div>

                  <!-- Register Form -->
                  <div v-if="loyaltyTab === 'register'">
                    <div v-if="registerSuccessMsg" class="p-3 rounded bg-success-subtle border border-success text-center">
                      <i class="fa-solid fa-envelope-circle-check text-success fa-2x mb-2"></i>
                      <h6 class="fw-bold text-success mb-1">Verifikasi Email Dikirim!</h6>
                      <p class="small text-muted mb-2">{{ registerSuccessMsg }}</p>
                      <button type="button" class="btn btn-sm btn-success" @click="checkUserSession">
                        <i class="fa-solid fa-rotate me-1"></i> Saya Sudah Verifikasi Email
                      </button>
                    </div>

                    <div v-else>
                      <div class="row g-2 mb-2">
                        <div class="col-sm-7">
                          <label class="form-label small fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                          <input type="text" v-model="regForm.name" class="form-input" placeholder="Nama Lengkap" required />
                        </div>
                        <div class="col-sm-5">
                          <label class="form-label small fw-bold">Jenis Kelamin <span class="text-danger">*</span></label>
                          <div class="d-flex gap-2 pt-1">
                            <label class="small"><input type="radio" value="Laki-laki" v-model="regForm.gender"> Laki-laki</label>
                            <label class="small"><input type="radio" value="Perempuan" v-model="regForm.gender"> Perempuan</label>
                          </div>
                        </div>
                      </div>

                      <div class="row g-2 mb-2">
                        <div class="col-sm-6">
                          <label class="form-label small fw-bold">Email Valid <span class="text-danger">*</span></label>
                          <input type="email" v-model="regForm.email" class="form-input" placeholder="nama@gmail.com" required />
                        </div>
                        <div class="col-sm-6">
                          <label class="form-label small fw-bold">Password Login <span class="text-danger">*</span></label>
                          <input type="password" v-model="regForm.password" class="form-input" placeholder="Min. 6 karakter" required />
                        </div>
                      </div>

                      <div class="row g-2 mb-2">
                        <div class="col-sm-6">
                          <label class="form-label small fw-bold">No. HP / WhatsApp <span class="text-danger">*</span></label>
                          <input type="text" v-model="regForm.whatsapp" class="form-input font-mono" placeholder="081234567890" required />
                        </div>
                        <div class="col-sm-6">
                          <label class="form-label small fw-bold">Pekerjaan Saat Ini <span class="text-danger">*</span></label>
                          <input type="text" v-model="regForm.occupation" class="form-input" placeholder="Contoh: Karyawan Swasta" required />
                        </div>
                      </div>

                      <div class="mb-3">
                        <label class="form-label small fw-bold">Alamat Pengiriman Lengkap <span class="text-danger">*</span></label>
                        <textarea v-model="regForm.address" rows="2" class="form-textarea" placeholder="Jalan, nomor rumah, RT/RW..." required></textarea>
                      </div>

                      <button type="button" class="btn btn-warning w-100 fw-bold py-2" @click="submitLoyaltyRegister" :disabled="registering">
                        <i v-if="registering" class="fa-solid fa-spinner fa-spin me-1"></i>
                        <i v-else class="fa-solid fa-paper-plane me-1"></i>
                        <span>{{ registering ? 'Mendaftarkan...' : 'Daftar & Kirim Email Verifikasi' }}</span>
                      </button>
                    </div>
                  </div>

                  <!-- Login Form -->
                  <div v-else>
                    <div class="mb-2">
                      <label class="form-label small fw-bold">Email Terdaftar <span class="text-danger">*</span></label>
                      <input type="email" v-model="loginForm.email" class="form-input" placeholder="nama@gmail.com" required />
                    </div>
                    <div class="mb-3">
                      <label class="form-label small fw-bold">Password Login <span class="text-danger">*</span></label>
                      <input type="password" v-model="loginForm.password" class="form-input" placeholder="Password Anda" required />
                    </div>
                    <button type="button" class="btn btn-primary w-100 fw-bold py-2" @click="submitLoyaltyLogin" :disabled="loggingIn">
                      <i v-if="loggingIn" class="fa-solid fa-spinner fa-spin me-1"></i>
                      <i v-else class="fa-solid fa-right-to-bracket me-1"></i>
                      <span>{{ loggingIn ? 'Memverifikasi...' : 'Masuk Akun' }}</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Option B: Beli Makanan Saja (Guest Checkout) -->
              <div v-else class="p-3 rounded bg-light border mb-3">
                <div class="p-2 mb-3 rounded bg-white border small text-muted">
                  <i class="fa-solid fa-circle-info text-primary me-1"></i>
                  Pemesanan langsung tanpa verifikasi email. Lengkapi formulir di bawah ini untuk pengantaran pesanan:
                </div>

                <div class="mb-2">
                  <label class="form-label small fw-bold">Nama Lengkap Pemesan <span class="text-danger">*</span></label>
                  <input type="text" v-model="clientForm.name" class="form-input" placeholder="Contoh: Dimas Wicaksono" required />
                </div>

                <div class="row g-2 mb-2">
                  <div class="col-sm-6">
                    <label class="form-label small fw-bold">Nomor WhatsApp <span class="text-danger">*</span></label>
                    <input type="tel" v-model="clientForm.whatsapp" class="form-input" placeholder="081234567890" required />
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label small fw-bold">Email Aktif <span class="text-danger">*</span></label>
                    <input type="email" v-model="clientForm.email" class="form-input" placeholder="dimas@gmail.com" required />
                  </div>
                </div>

                <div class="mb-2">
                  <label class="form-label small fw-bold">Alamat Pengiriman Lengkap <span class="text-danger">*</span></label>
                  <textarea v-model="clientForm.address" rows="2" class="form-textarea" placeholder="Jalan, nomor rumah, RT/RW, patokan..." required></textarea>
                </div>
              </div>
            </div>

            <!-- 4. Payment Method Selection -->
            <div class="form-section mb-4">
              <h5 class="section-subtitle">
                <i class="fa-solid fa-wallet text-warning me-2"></i> Metode Pembayaran
              </h5>

              <div class="payment-options">
                <!-- Method 1: Payment Gateway if configured -->
                <label v-if="store.payment_type === 'payment_gateway'" :class="['payment-card', clientForm.payment_method === 'Payment Gateway (Mayar.id)' ? 'selected' : '']">
                  <input type="radio" value="Payment Gateway (Mayar.id)" v-model="clientForm.payment_method" />
                  <div class="payment-card-body">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <span class="fw-bold"><i class="fa-solid fa-credit-card text-success me-1"></i> Payment Gateway (Mayar.id)</span>
                      <span class="badge badge-primary">Online Otomatis</span>
                    </div>
                    <p class="text-muted small mb-0">Bayar online instan via QRIS Otomatis, Virtual Account Bank & E-Wallet.</p>
                  </div>
                </label>

                <!-- Method 2: QRIS Statik / Dinamis -->
                <label v-else :class="['payment-card', (clientForm.payment_method === 'QRIS Dinamis' || clientForm.payment_method === 'QRIS Statik') ? 'selected' : '']">
                  <input type="radio" value="QRIS Statik" v-model="clientForm.payment_method" />
                  <div class="payment-card-body">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <span class="fw-bold"><i class="fa-solid fa-qrcode text-primary me-1"></i> Bayar Langsung QRIS Statik Toko</span>
                      <span class="badge badge-primary">Rekomendasi</span>
                    </div>
                    <p class="text-muted small mb-0">Scan barcode QRIS toko dari mobile banking (BCA, Mandiri, BRI, GoPay, OVO, ShopeePay).</p>
                  </div>
                </label>

                <!-- Method 3: COD -->
                <label :class="['payment-card', clientForm.payment_method === 'Bayar Terima Makanan' ? 'selected' : '']">
                  <input type="radio" value="Bayar Terima Makanan" v-model="clientForm.payment_method" />
                  <div class="payment-card-body">
                    <span class="fw-bold d-block mb-1"><i class="fa-solid fa-hand-holding-dollar text-success me-1"></i> Bayar Ketika Terima Makanan</span>
                    <p class="text-muted small mb-0">Bayar tunai/transfer saat makanan sampai di lokasi atau diambil.</p>
                  </div>
                </label>
              </div>

              <!-- Dynamic QRIS Preview Box if QRIS chosen -->
              <div v-if="clientForm.payment_method === 'QRIS Dinamis' || clientForm.payment_method === 'QRIS Statik'" class="qris-preview-box mt-3">
                <div class="text-center">
                  <span class="badge badge-dark mb-2">QRIS RESMI {{ store.name }}</span>
                  <div class="qris-img-container">
                    <img :src="store.qris_image || 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=NAUMIL_RICE_PAYMENT'" 
                         alt="QRIS Toko" class="qris-img" />
                  </div>
                  <div class="mt-2">
                    <button type="button" class="btn btn-outline-primary btn-sm fw-bold shadow-sm" @click="downloadCampaignQris">
                      <i class="fa-solid fa-download me-1"></i> Unduh / Simpan Gambar QRIS
                    </button>
                  </div>
                  <small class="text-muted d-block mt-2 font-mono">
                    Nominal: <strong>Rp {{ formatNumber(finalPayableTotal) }}</strong>
                  </small>
                  <small class="text-muted d-block mt-1">
                    Silakan simpan barcode atau scan langsung setelah pesanan dikonfirmasi.
                  </small>

                  <!-- Reassurance Cashback Poin Pelanggan Setia -->
                  <div v-if="campaign.discount_type === 'min_qty' && totalCartQty < (campaign.min_order_qty || 15)" 
                       class="card p-3 mt-3 border-warning-subtle bg-warning-subtle text-start shadow-xs">
                    <div class="d-flex align-items-start gap-2">
                      <i class="fa-solid fa-coins text-warning fs-4 mt-1 flex-shrink-0"></i>
                      <div class="small text-dark">
                        <strong class="d-block text-dark mb-1">
                          <i class="fa-solid fa-crown text-warning me-1"></i> Info Cashback Poin Pelanggan Setia
                        </strong>
                        <span style="line-height: 1.45;" class="d-block text-secondary">
                          Jika pesanan Anda saat ini belum mencapai batas minimum order ({{ campaign.min_order_qty || 15 }} porsi) dan sudah bayar via QRIS, <strong>tenang saja!</strong> Jika batch Open PO ini nantinya melebihi dari batas minimum dari pesanan pelanggan lain dan kamu daftar / masuk sebagai <strong>Pelanggan Setia</strong>, selisih cashback bisa ditukar dengan <strong>Poin</strong>, dan poin bisa dipakai untuk mengurangi total bayar menu di pesanan selanjutnya!
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 5. Items Summary Table -->
            <div class="form-section mb-4">
              <h5 class="section-subtitle">
                <i class="fa-solid fa-receipt text-primary me-2"></i> Ringkasan Pesanan ({{ cart.length }} Menu)
              </h5>
              <div class="cart-items-summary">
                <div v-for="(item, idx) in cart" :key="idx" class="summary-item">
                  <div class="flex-grow-1">
                    <div class="fw-bold">{{ item.menu_name }} <span class="text-muted">x{{ item.quantity }}</span></div>
                    <small class="text-muted d-block" v-if="item.selected_options?.length">
                      + {{ item.selected_options.map(o => o.name).join(', ') }}
                    </small>
                    <small class="text-amber d-block" v-if="item.special_notes">
                      Catatan: {{ item.special_notes }}
                    </small>
                  </div>
                  <div class="fw-bold font-mono">Rp {{ formatNumber(item.subtotal) }}</div>
                  <button class="remove-btn" @click="removeFromCart(idx)" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                </div>
              </div>

              <!-- Promo Alert Banner in Order Summary -->
              <div v-if="campaign.discount_type === 'min_qty' && totalCartQty < (campaign.min_order_qty || 15)" 
                   class="p-3 rounded border border-warning bg-warning-subtle mb-3 text-dark small">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="fa-solid fa-circle-info text-warning fs-4 flex-shrink-0"></i>
                  <div>
                    <strong>Promo Open PO:</strong> Tambah <strong>{{ (campaign.min_order_qty || 15) - totalCartQty }} porsi</strong> lagi untuk langsung mendapatkan diskon promo all menu!
                  </div>
                </div>
                <div class="p-2 rounded bg-white border border-warning-subtle small text-muted">
                  <div class="text-primary fw-bold mb-1">
                    <i class="fa-solid fa-coins text-warning me-1"></i> Sudah bayar QRIS tapi target PO belum tercapai?
                  </div>
                  <span style="font-size: 0.82rem; line-height: 1.45;" class="d-block text-dark">
                    <strong>Tenang saja!</strong> Jika batch Open PO ini nantinya melebihi dari batas minimum dan kamu daftar menjadi <strong>Pelanggan Setia</strong>, cashback bisa ditukar dengan <strong>Point</strong>, dan point bisa dipakai untuk mengurangi total bayar menu pada pesanan berikutnya.
                  </span>
                </div>
              </div>
              <div v-else-if="campaign.discount_type === 'min_qty' && totalCartQty >= (campaign.min_order_qty || 15)" 
                   class="p-3 rounded border border-success bg-success-subtle mb-3 text-success-emphasis small d-flex align-items-center gap-2">
                <i class="fa-solid fa-circle-check text-success fs-4 flex-shrink-0"></i>
                <div>
                  <strong>Selamat! Syarat Promo Terpenuhi:</strong> Minimum pesanan {{ campaign.min_order_qty || 15 }} porsi tercapai. Diskon telah otomatis diterapkan pada seluruh pesanan Anda.
                </div>
              </div>

              <!-- Totals -->
              <div class="totals-breakdown mt-3">
                <div class="total-row">
                  <span>Subtotal Makanan:</span>
                  <span class="fw-bold font-mono">Rp {{ formatNumber(cartSubtotal) }}</span>
                </div>
                <div v-if="calculatedDiscount > 0" class="total-row text-danger">
                  <span>Diskon Promo Campaign:</span>
                  <span class="fw-bold font-mono">- Rp {{ formatNumber(calculatedDiscount) }}</span>
                </div>
                <div class="total-row grand-total mt-2 pt-2 border-top">
                  <span>Total Akhir:</span>
                  <span class="text-primary fs-5 fw-bold font-mono">Rp {{ formatNumber(finalPayableTotal) }}</span>
                </div>
              </div>
            </div>

            <!-- Mayar Payment Gateway Waiting Box -->
            <div v-if="mayarPaymentWaiting" class="card p-3 mb-3 border-success bg-light text-start shadow-sm">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold text-success"><i class="fa-solid fa-credit-card me-1"></i> Pembayaran Mayar.id Dibuka</span>
                <span class="badge bg-warning text-dark font-mono"><i class="fa-solid fa-spinner fa-spin me-1"></i> Menunggu Bayar</span>
              </div>
              <p class="small text-muted mb-2">
                Jendela pembayaran dibuka otomatis dalam window kecil. Anda juga dapat mengklik tombol di bawah jika jendela belum muncul:
              </p>
              <button type="button" class="btn btn-danger w-100 py-2 fw-bold text-white mb-2 border-0" @click="openMayarPopup(paymentGatewayUrl, currentOrderNumber)">
                <i class="fa-solid fa-window-restore me-1"></i> Buka Window Bayar Mayar
              </button>
              <small class="text-muted d-block text-center" style="font-size: 0.78rem;">
                <i class="fa-solid fa-circle-notch fa-spin text-success me-1"></i> Window kecil otomatis tertutup dan halaman langsung menuju tracing order setelah pembayaran berhasil.
              </small>
            </div>

            <!-- Submit Order Button -->
            <button :disabled="submitting || mayarPaymentWaiting" class="btn btn-primary w-100 py-3 fs-6" @click="submitOrder">
              <span v-if="submitting">
                <i class="fa-solid fa-spinner fa-spin me-2"></i> Memproses Pesanan...
              </span>
              <span v-else>
                <i class="fa-solid fa-circle-check me-2"></i> Konfirmasi & Submit Pre-Order
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import axios from 'axios'
import confetti from 'canvas-confetti'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  token: {
    type: String,
    required: true
  }
})

const loading = ref(true)
const submitting = ref(false)
const error = ref(false)
const errorMessage = ref('')

const campaign = ref({})
const store = ref({})
const menus = ref([])
const isExpired = ref(false)

const activeCategory = ref('all')
const cart = ref([])

// Save cart to local storage and sync with navbar cart badge
watch(cart, (newVal) => {
  if (newVal && newVal.length > 0) {
    localStorage.setItem('naumil_campaign_cart_' + props.token, JSON.stringify(newVal))
    localStorage.setItem('naumil_customer_cart_backup', JSON.stringify(newVal))
    if (store.value?.slug) localStorage.setItem('naumil_last_store_slug', store.value.slug)
    localStorage.setItem('naumil_return_store_url', window.location.href)
  } else {
    localStorage.removeItem('naumil_campaign_cart_' + props.token)
  }

  try {
    window.dispatchEvent(new CustomEvent('naumil-cart-updated', {
      detail: {
        count: totalCartQty.value,
        total: cartSubtotal.value
      }
    }))
  } catch (e) {}
}, { deep: true })

// Modal States
const activeMenuModal = ref(null)
const selectedToppings = ref([])
const selectedUpsize = ref(null)
const selectedCondition = ref(null)
const modalSpecialNotes = ref('')
const modalQty = ref(1)

// Checkout States
const showCheckoutModal = ref(false)
const checkoutMode = ref('loyalty') // 'loyalty' or 'guest'
const loyaltyTab = ref('register') // 'register' or 'login'
const isReturningUser = ref(false)
const clientConcat = ref('')
const customerLoyaltyPoints = ref(0)
const registering = ref(false)
const loggingIn = ref(false)
const registerSuccessMsg = ref('')
const mayarPaymentWaiting = ref(false)
const paymentGatewayUrl = ref('')
const currentOrderNumber = ref('')

// Form Pelanggan Setia Baru
const regForm = ref({
  name: '',
  gender: 'Laki-laki',
  email: '',
  password: '',
  whatsapp: '',
  occupation: 'Karyawan Swasta',
  address: ''
})

// Form Login Pelanggan Setia
const loginForm = ref({
  email: '',
  password: ''
})

// Form Pemesanan Aktif / Guest
const clientForm = ref({
  name: '',
  whatsapp: '',
  email: '',
  address: '',
  payment_method: 'QRIS Dinamis',
  notes: ''
})

// Countdown Timer
const countdownText = ref('')
let timerInterval = null

const fetchCampaign = async () => {
  try {
    loading.value = true
    error.value = false
    const res = await axios.get(`/api/v1/campaigns/${props.token}`)
    if (res.data.success) {
      campaign.value = res.data.data.campaign
      store.value = res.data.data.store
      menus.value = res.data.data.menus
      isExpired.value = res.data.is_expired
      if (store.value?.payment_type === 'payment_gateway') {
        clientForm.value.payment_method = 'Payment Gateway (Mayar.id)'
      }
      startCountdown()
    }
  } catch (err) {
    error.value = true
    errorMessage.value = err.response?.data?.message || 'Gagal memuat campaign Open PO.'
  } finally {
    loading.value = false
  }
}

const applyLoyaltyUser = (user) => {
  clientForm.value.name = user.name || ''
  clientForm.value.whatsapp = user.whatsapp || ''
  clientForm.value.email = user.email || ''
  clientForm.value.address = user.address || clientForm.value.address || ''
  clientConcat.value = `${user.name} (${user.email})`
  customerLoyaltyPoints.value = user.loyalty_points || 0
  isReturningUser.value = true
  localStorage.setItem('naumil_customer_session', JSON.stringify(user))
  localStorage.setItem('naumil_user', JSON.stringify(user))
}

const submitLoyaltyRegister = async () => {
  if (!regForm.value.name || !regForm.value.email || !regForm.value.password || !regForm.value.whatsapp) {
    alert('Mohon lengkapi Nama Lengkap, Email Valid, Password, dan Nomor WhatsApp.')
    return
  }

  try {
    registering.value = true
    // Backup cart to local storage so draft is preserved
    localStorage.setItem('naumil_campaign_cart_' + props.token, JSON.stringify(cart.value))

    const res = await axios.post('/api/v1/customer/register-loyalty', {
      ...regForm.value,
      store_ids: store.value?.id ? [store.value.id] : []
    })

    if (res.data.success) {
      registerSuccessMsg.value = res.data.message
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal mendaftar Pelanggan Setia.')
  } finally {
    registering.value = false
  }
}

const submitLoyaltyLogin = async () => {
  if (!loginForm.value.email || !loginForm.value.password) {
    alert('Mohon masukkan Email dan Password.')
    return
  }

  try {
    loggingIn.value = true
    const res = await axios.post('/api/v1/customer/login-loyalty', loginForm.value)
    if (res.data.success) {
      applyLoyaltyUser(res.data.client)
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Email atau password salah atau akun belum diaktifkan.')
  } finally {
    loggingIn.value = false
  }
}

const checkUserSession = async () => {
  try {
    const res = await axios.get('/api/v1/customer/current-session')
    if (res.data?.authenticated && res.data?.client) {
      applyLoyaltyUser(res.data.client)
      registerSuccessMsg.value = ''
      alert('Selamat! Akun Pelanggan Setia Anda aktif dan terverifikasi.')
    } else {
      alert('Akun belum terverifikasi atau belum login. Silakan cek inbox/spam email Anda untuk klik link verifikasi.')
    }
  } catch (e) {
    alert('Gagal memeriksa status aktivasi akun.')
  }
}

const checkBrowserCache = async () => {
  const cached = localStorage.getItem('naumil_customer_session') || localStorage.getItem('naumil_user')
  if (cached) {
    try {
      const user = JSON.parse(cached)
      if (user.email) {
        applyLoyaltyUser(user)
        return
      }
    } catch (e) {
      localStorage.removeItem('naumil_customer_session')
      localStorage.removeItem('naumil_user')
    }
  }

  try {
    const res = await axios.get('/api/v1/customer/current-session')
    if (res.data?.authenticated && res.data?.client) {
      applyLoyaltyUser(res.data.client)
    }
  } catch (e) {}
}

const clearReturningUser = async () => {
  isReturningUser.value = false
  clientConcat.value = ''
  customerLoyaltyPoints.value = 0
  clientForm.value.name = ''
  clientForm.value.whatsapp = ''
  clientForm.value.email = ''
  clientForm.value.address = ''
  localStorage.removeItem('naumil_customer_session')
  localStorage.removeItem('naumil_user')

  try {
    await axios.post('/api/v1/customer/logout')
  } catch (e) {}
}

// Categories & Filtering
const uniqueCategories = computed(() => {
  const cats = menus.value.map(m => m.category_name).filter(Boolean)
  return [...new Set(cats)]
})

const filteredMenus = computed(() => {
  if (activeCategory.value === 'all') return menus.value
  return menus.value.filter(m => m.category_name === activeCategory.value)
})

const isSoldOut = computed(() => {
  return campaign.value?.is_sold_out || (campaign.value?.max_quota !== null && campaign.value?.remaining_quota !== undefined && campaign.value?.remaining_quota <= 0)
})

const modalMaxQty = computed(() => {
  if (!activeMenuModal.value) return null
  if (activeMenuModal.value.remaining_quota !== null && activeMenuModal.value.remaining_quota !== undefined) {
    return activeMenuModal.value.remaining_quota
  }
  if (campaign.value?.remaining_quota !== null && campaign.value?.remaining_quota !== undefined) {
    return campaign.value.remaining_quota
  }
  return null
})

// Customization Modal Logic
const openOptionModal = (menu) => {
  activeMenuModal.value = menu
  selectedToppings.value = []
  selectedUpsize.value = menu.options?.upsizes?.[0] || null
  selectedCondition.value = menu.options?.conditions?.[0] || null
  modalSpecialNotes.value = ''
  modalQty.value = 1
}

const incrementModalQty = () => {
  if (modalMaxQty.value === null || modalQty.value < modalMaxQty.value) {
    modalQty.value++
  }
}

const decrementModalQty = () => {
  modalQty.value = Math.max(1, modalQty.value - 1)
}

const modalItemSubtotal = computed(() => {
  if (!activeMenuModal.value) return 0
  let base = activeMenuModal.value.final_price
  let upsizePrice = selectedUpsize.value?.price || 0
  let toppingsPrice = selectedToppings.value.reduce((sum, t) => sum + (t.price || 0), 0)
  let conditionPrice = selectedCondition.value?.price || 0
  return (base + upsizePrice + toppingsPrice + conditionPrice) * modalQty.value
})

const addToCartFromModal = () => {
  if (modalMaxQty.value !== null && modalQty.value > modalMaxQty.value) {
    alert(`Maaf, kuota pemesanan untuk menu ini hanya tersisa ${modalMaxQty.value} porsi.`)
    modalQty.value = modalMaxQty.value
    return
  }

  const options = []
  if (selectedUpsize.value) options.push(selectedUpsize.value)
  if (selectedCondition.value) options.push(selectedCondition.value)
  selectedToppings.value.forEach(t => options.push(t))

  const unitPrice = modalItemSubtotal.value / modalQty.value

  cart.value.push({
    menu_id: activeMenuModal.value.id,
    menu_name: activeMenuModal.value.name,
    quantity: modalQty.value,
    price: unitPrice,
    subtotal: modalItemSubtotal.value,
    selected_options: options,
    special_notes: modalSpecialNotes.value
  })

  activeMenuModal.value = null
}

const removeFromCart = (index) => {
  cart.value.splice(index, 1)
  if (cart.value.length === 0) {
    localStorage.removeItem('naumil_campaign_cart_' + props.token)
  }
}

// Cart Totals
const totalCartQty = computed(() => {
  return cart.value.reduce((acc, it) => acc + it.quantity, 0)
})

const cartSubtotal = computed(() => {
  return cart.value.reduce((acc, it) => acc + it.subtotal, 0)
})

const calculatedDiscount = computed(() => {
  if (!campaign.value) return 0
  const sub = cartSubtotal.value
  const totalQty = totalCartQty.value

  if (campaign.value.discount_type === 'min_qty') {
    const minQty = Number(campaign.value.min_order_qty) || 0
    // Kondisi promo saat open PO: minimum qty pembelian all menu (misal >= 15)
    if (minQty > 0 && totalQty >= minQty) {
      let totalDisc = 0
      cart.value.forEach(item => {
        const m = (menus.value || []).find(it => it.id === item.menu_id)
        const menuDisc = m ? (Number(m.discount_price) || 0) : 0
        const basePrice = m ? (Number(m.custom_price) || Number(m.original_price) || 0) : item.price

        // Urutan: cek dulu permenu ada diskon atau tidak, jika tidak ada maka pakai persentase yang ditentukan
        if (menuDisc > 0) {
          totalDisc += (menuDisc * item.quantity)
        } else {
          const pct = Number(campaign.value.discount_value) || 0
          totalDisc += ((basePrice * pct) / 100) * item.quantity
        }
      })
      return totalDisc
    }
    return 0
  }

  if (campaign.value.discount_type === 'fixed') {
    return Math.min(sub, Number(campaign.value.discount_value) || 0)
  }

  if (campaign.value.discount_type === 'percentage') {
    return (sub * (Number(campaign.value.discount_value) || 0)) / 100
  }

  return 0
})

const finalPayableTotal = computed(() => {
  return Math.max(0, cartSubtotal.value - calculatedDiscount.value)
})

// Mayar.id Popup Window & Auto-close Polling
let mayarPopup = null
let paymentPollingInterval = null
let messageListener = null

const openMayarPopup = (paymentUrl, orderNumber) => {
  if (!paymentUrl) return
  paymentGatewayUrl.value = paymentUrl
  currentOrderNumber.value = orderNumber
  mayarPaymentWaiting.value = true

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
  mayarPaymentWaiting.value = false
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

const downloadCampaignQris = async () => {
  const url = store.value?.qris_image
  if (!url) return
  const storeName = store.value?.name || 'Naumil'
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

// Submit Order Logic
const submitOrder = async () => {
  if (checkoutMode.value === 'loyalty') {
    if (!isReturningUser.value) {
      alert('Silakan Masuk atau Daftarkan Akun Pelanggan Setia terlebih dahulu, atau pilih opsi "Beli Makanan Saja".')
      return
    }
    if (!clientForm.value.address) {
      alert('Mohon isi Alamat Pengiriman Lengkap.')
      return
    }
  } else {
    // Guest checkout
    if (!clientForm.value.name || !clientForm.value.whatsapp || !clientForm.value.email || !clientForm.value.address) {
      alert('Mohon lengkapi Nama Lengkap, Nomor HP/WhatsApp, Email Aktif, dan Alamat Pengiriman.')
      return
    }
  }

  if (cart.value.length === 0) {
    alert('Keranjang belanja kosong.')
    return
  }

  try {
    submitting.value = true
    const payload = {
      store_id: store.value.id,
      campaign_id: campaign.value.id,
      order_type: checkoutMode.value, // 'loyalty' or 'guest'
      customer_name: clientForm.value.name,
      customer_whatsapp: clientForm.value.whatsapp,
      customer_email: clientForm.value.email,
      customer_address: clientForm.value.address,
      payment_method: clientForm.value.payment_method,
      items: cart.value.map(it => ({
        menu_id: it.menu_id,
        quantity: it.quantity,
        selected_options: it.selected_options,
        special_notes: it.special_notes
      }))
    }

    const res = await axios.post('/api/v1/orders', payload)
    if (res.data.success) {
      // Clear cart cache
      localStorage.removeItem('naumil_campaign_cart_' + props.token)
      cart.value = []

      // Cache user in browser if loyalty
      if (checkoutMode.value === 'loyalty' && res.data.data.client) {
        applyLoyaltyUser(res.data.data.client)
      }

      // Confetti celebration
      confetti({
        particleCount: 120,
        spread: 80,
        origin: { y: 0.6 }
      })

      // Navigate or open Mayar small popup
      const orderNumber = res.data.data.order_number
      const paymentUrl = res.data.data.payment_url

      if (paymentUrl) {
        openMayarPopup(paymentUrl, orderNumber)
      } else {
        setTimeout(() => {
          router.visit('/trace/' + orderNumber)
        }, 1000)
      }
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal mengirim pesanan. Silakan coba lagi.')
  } finally {
    submitting.value = false
  }
}

// Countdown Calculation
const startCountdown = () => {
  if (!campaign.value.close_order_at) return
  const update = () => {
    const diff = new Date(campaign.value.close_order_at) - new Date()
    if (diff <= 0) {
      countdownText.value = 'Masa close order telah berakhir'
      isExpired.value = true
      clearInterval(timerInterval)
      return
    }
    const days = Math.floor(diff / (1000 * 60 * 60 * 24))
    const hours = Math.floor((diff / (1000 * 60 * 60)) % 24)
    const mins = Math.floor((diff / 1000 / 60) % 60)
    const secs = Math.floor((diff / 1000) % 60)
    countdownText.value = `${days}h ${hours}j ${mins}m ${secs}d lagi tersisa`
  }
  update()
  timerInterval = setInterval(update, 1000)
}

const formatNumber = (num) => {
  if (!num) return '0'
  return new Intl.NumberFormat('id-ID').format(num)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', {
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

onMounted(() => {
  fetchCampaign()
  checkBrowserCache()

  // Restore draft cart if any
  const savedCart = localStorage.getItem('naumil_campaign_cart_' + props.token)
  if (savedCart && cart.value.length === 0) {
    try {
      cart.value = JSON.parse(savedCart)
    } catch (e) {}
  }

  // Listen for open cart event from header navbar
  window.addEventListener('open-naumil-cart', () => {
    showCheckoutModal.value = true
  })

  // Initial cart sync to navbar
  setTimeout(() => {
    try {
      window.dispatchEvent(new CustomEvent('naumil-cart-updated', {
        detail: {
          count: totalCartQty.value,
          total: cartSubtotal.value
        }
      }))
    } catch (e) {}
  }, 100)
})

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
})
</script>

<style scoped>
.campaign-page {
  padding-bottom: 6rem;
}

.max-w-500 {
  max-width: 500px;
}

/* Store Badge Bar */
.store-badge-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 0;
  border-bottom: 1px solid var(--border);
}

.store-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.store-avatar {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-md);
  object-fit: cover;
  border: 2px solid white;
  box-shadow: var(--shadow-sm);
}

.store-name {
  font-size: 1.25rem;
  font-weight: 800;
}

.btn-wa-sm {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: white;
  border: 1px solid var(--border);
  padding: 8px 14px;
  border-radius: var(--radius-md);
  font-weight: 600;
  font-size: 0.88rem;
  transition: var(--transition);
}

.btn-wa-sm:hover {
  background: #f0fdf4;
  border-color: #86efac;
}

/* Campaign Banner */
.campaign-banner-card {
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.banner-img-wrapper {
  position: relative;
  height: 320px;
}

.banner-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.banner-gradient {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.2) 0%, rgba(15, 23, 42, 0.85) 100%);
}

.banner-text {
  position: absolute;
  bottom: 2rem;
  left: 2rem;
  right: 2rem;
  color: white;
}

.campaign-headline {
  font-size: 2.2rem;
  color: white;
  margin-bottom: 0.5rem;
  line-height: 1.2;
}

.campaign-lead {
  color: rgba(255, 255, 255, 0.9);
  font-size: 1rem;
  max-width: 680px;
  line-height: 1.5;
}

/* Schedule Bar */
.schedule-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-around;
  padding: 1.5rem;
  background: white;
  gap: 1.5rem;
}

.schedule-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.schedule-label {
  display: block;
  font-size: 0.78rem;
  color: var(--text-muted);
}

.schedule-value {
  font-size: 1rem;
  color: var(--dark);
}

.schedule-divider {
  width: 1px;
  height: 40px;
  background-color: var(--border);
}

/* Expired Alert */
.alert-expired {
  background: #fef2f2;
  border: 1.5px solid #fecaca;
  padding: 1.5rem;
  border-radius: var(--radius-md);
}

/* Category Tabs */
.category-tabs {
  display: flex;
  gap: 10px;
  overflow-x: auto;
  padding-bottom: 6px;
}

.tab-btn {
  background: white;
  border: 1px solid var(--border);
  padding: 8px 18px;
  border-radius: var(--radius-full);
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  white-space: nowrap;
  transition: var(--transition);
}

.tab-btn:hover {
  border-color: var(--primary);
  color: var(--primary);
}

.tab-btn.active {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
  box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25);
}

/* Menu Card */
.menu-card {
  display: flex;
  flex-direction: column;
}

.menu-image-box {
  position: relative;
  height: 200px;
}

.menu-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.discount-badge {
  position: absolute;
  top: 10px;
  left: 10px;
  background: var(--primary);
  color: white;
  font-size: 0.72rem;
  font-weight: 800;
  padding: 4px 8px;
  border-radius: var(--radius-sm);
  box-shadow: var(--shadow-sm);
}

.stock-badge {
  position: absolute;
  bottom: 10px;
  right: 10px;
  background: rgba(15, 23, 42, 0.75);
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: var(--radius-sm);
}

.stock-badge.low {
  background: #dc2626;
}

.menu-card-body {
  padding: 1.25rem;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.category-tag {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 4px;
}

.menu-name {
  font-size: 1.15rem;
  margin-bottom: 6px;
}

.menu-desc {
  font-size: 0.85rem;
  color: var(--text-muted);
  line-height: 1.4;
  margin-bottom: 1rem;
  flex: 1;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.menu-pricing {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
}

.old-price {
  font-size: 0.82rem;
  color: #94a3b8;
  text-decoration: line-through;
  display: block;
}

.final-price {
  font-size: 1.2rem;
  font-weight: 800;
  color: var(--primary);
  font-family: var(--font-display);
}

.btn-add {
  padding: 8px 16px;
  font-size: 0.88rem;
}

/* Modal Styling */
.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.close-btn {
  background: transparent;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: var(--text-muted);
}

.modal-thumb {
  width: 64px;
  height: 64px;
  border-radius: var(--radius-sm);
  object-fit: cover;
}

.option-title {
  display: block;
  font-weight: 700;
  font-size: 0.9rem;
  margin-bottom: 8px;
}

.option-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.option-radio-label, .option-checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-size: 0.9rem;
  transition: var(--transition);
}

.option-radio-label:hover, .option-checkbox-label:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.qty-selector-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 1rem;
  border-top: 1px solid var(--border);
}

.qty-control {
  display: flex;
  align-items: center;
  gap: 12px;
}

.qty-btn {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: 1px solid var(--border);
  background: white;
  font-size: 1.1rem;
  font-weight: bold;
  cursor: pointer;
}

.qty-num {
  font-size: 1.15rem;
  font-weight: bold;
  min-width: 24px;
  text-align: center;
}

/* Floating Cart Bar */
.floating-cart-bar {
  position: fixed;
  bottom: 20px;
  left: 20px;
  right: 20px;
  max-width: 680px;
  margin: 0 auto;
  background: var(--dark);
  color: white;
  border-radius: var(--radius-full);
  padding: 10px 18px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
  z-index: 90;
  animation: slideUp 0.3s ease;
}

.cart-bar-info {
  display: flex;
  align-items: center;
  gap: 14px;
}

.cart-pill {
  background: var(--primary);
  color: white;
  padding: 6px 14px;
  border-radius: var(--radius-full);
  font-weight: 700;
  font-size: 0.85rem;
}

.btn-checkout {
  background: var(--primary);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: var(--radius-full);
  font-weight: 700;
  cursor: pointer;
  box-shadow: var(--shadow-glow);
}

/* Checkout Modal */
.modal-checkout {
  max-width: 650px;
}

.section-subtitle {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 1rem;
  padding-bottom: 6px;
  border-bottom: 1px solid var(--border);
}

.alert-returning {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  padding: 10px 14px;
  border-radius: var(--radius-sm);
}

.btn-text-sm {
  background: transparent;
  border: none;
  color: var(--primary);
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
}

.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.payment-options {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.payment-card {
  display: flex;
  gap: 12px;
  padding: 12px;
  border: 1.5px solid var(--border);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: var(--transition);
}

.payment-card.selected {
  border-color: var(--primary);
  background: #fff5f5;
}

.qris-preview-box {
  background: #f8fafc;
  border: 1px dashed var(--border);
  padding: 1.25rem;
  border-radius: var(--radius-md);
}

.qris-img-container {
  background: white;
  padding: 10px;
  border-radius: var(--radius-sm);
  display: inline-block;
  box-shadow: var(--shadow-sm);
}

.qris-img {
  width: 180px;
  height: 180px;
  object-fit: contain;
}

.cart-items-summary {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 180px;
  overflow-y: auto;
}

.summary-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 6px 0;
  border-bottom: 1px solid #f1f5f9;
}

.remove-btn {
  background: transparent;
  border: none;
  color: #ef4444;
  cursor: pointer;
  padding: 4px;
}

.totals-breakdown {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 0.92rem;
}

.total-row {
  display: flex;
  justify-content: space-between;
}

.grand-total {
  font-size: 1.1rem;
}

@media (max-width: 768px) {
  .banner-img-wrapper {
    height: 260px;
  }
  .campaign-headline {
    font-size: 1.6rem;
  }
  .schedule-bar {
    flex-direction: column;
    align-items: flex-start;
  }
  .schedule-divider {
    display: none;
  }
  .form-row-2 {
    grid-template-columns: 1fr;
  }
}
</style>
