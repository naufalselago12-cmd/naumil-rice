<template>
  <AppLayout>
    <div class="customer-profile-page py-4 py-md-5">
      <div class="container">
        
        <!-- ======================================================== -->
        <!-- SCENARIO A: CUSTOMER IS LOGGED IN & VERIFIED             -->
        <!-- ======================================================== -->
        <div v-if="isLoggedIn && customer" class="profile-dashboard">
          
          <!-- VIP Membership Card Header -->
          <div class="membership-vip-card mb-4 shadow">
            <div class="vip-pattern-overlay"></div>
            <div class="vip-card-content">
              <div class="row align-items-center g-3">
                <div class="col-md-7">
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge-vip">
                      <i class="fa-solid fa-crown text-warning me-1"></i> PELANGGAN SETIA
                    </span>
                    <span class="badge-verified">
                      <i class="fa-solid fa-circle-check text-success me-1"></i> Terverifikasi
                    </span>
                  </div>
                  <h2 class="vip-name mb-1">{{ customer.name }}</h2>
                  <div class="vip-meta d-flex flex-wrap gap-3 small text-white-50">
                    <span><i class="fa-solid fa-envelope me-1"></i> {{ customer.email }}</span>
                    <span><i class="fa-solid fa-phone me-1"></i> {{ customer.whatsapp }}</span>
                    <span v-if="customer.occupation"><i class="fa-solid fa-briefcase me-1"></i> {{ customer.occupation }}</span>
                  </div>
                </div>

                <div class="col-md-5 text-md-end">
                  <div class="points-display-box d-inline-block text-center p-3 rounded-4 shadow-sm">
                    <span class="points-label d-block text-warning-subtle small fw-bold mb-1">
                      <i class="fa-solid fa-coins text-warning me-1"></i> SALDO CREDIT POINT
                    </span>
                    <div class="points-value text-white fw-bold font-mono fs-1">
                      {{ formatNumber(customer.loyalty_points || 0) }}
                      <span class="fs-6 fw-normal text-warning">Pts</span>
                    </div>
                    <small class="text-white-50 d-block mt-1">Dapat digunakan saat checkout promo</small>
                  </div>
                </div>
              </div>

              <!-- Quick action links -->
              <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top border-white-10">
                <Link href="/" class="btn btn-warning btn-sm fw-bold">
                  <i class="fa-solid fa-utensils me-1"></i> Pesan Menu Sekarang
                </Link>
                <button type="button" class="btn btn-outline-light btn-sm" @click="activeTab = 'profile'">
                  <i class="fa-solid fa-user-pen me-1"></i> Edit Data Diri
                </button>
                <button type="button" class="btn btn-outline-light btn-sm" @click="activeTab = 'stores'">
                  <i class="fa-solid fa-store me-1"></i> Kelola Toko Langganan ({{ customer.stores?.length || 0 }})
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm ms-auto" @click="handleLogout">
                  <i class="fa-solid fa-right-from-bracket me-1"></i> Keluar
                </button>
              </div>
            </div>
          </div>

          <!-- Quick Stats Row -->
          <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
              <div class="stat-card p-3 rounded-4 bg-white shadow-sm border text-center h-100">
                <div class="stat-icon text-warning mb-1">
                  <i class="fa-solid fa-coins fa-2x"></i>
                </div>
                <div class="stat-value fw-bold fs-4 font-mono text-dark">{{ customer.loyalty_points || 0 }}</div>
                <div class="stat-label small text-muted">Saldo Poin Aktif</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="stat-card p-3 rounded-4 bg-white shadow-sm border text-center h-100">
                <div class="stat-icon text-primary mb-1">
                  <i class="fa-solid fa-bag-shopping fa-2x"></i>
                </div>
                <div class="stat-value fw-bold fs-4 font-mono text-dark">{{ customer.orders?.length || 0 }}</div>
                <div class="stat-label small text-muted">Total Transaksi</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="stat-card p-3 rounded-4 bg-white shadow-sm border text-center h-100">
                <div class="stat-icon text-success mb-1">
                  <i class="fa-solid fa-store fa-2x"></i>
                </div>
                <div class="stat-value fw-bold fs-4 font-mono text-dark">{{ customer.stores?.length || 0 }}</div>
                <div class="stat-label small text-muted">Cabang Langganan</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="stat-card p-3 rounded-4 bg-white shadow-sm border text-center h-100">
                <div class="stat-icon text-danger mb-1">
                  <i class="fa-solid fa-gift fa-2x"></i>
                </div>
                <div class="stat-value fw-bold fs-4 font-mono text-dark">{{ totalEarnedPoints }}</div>
                <div class="stat-label small text-muted">Total Poin Didapat</div>
              </div>
            </div>
          </div>

          <!-- Interactive Tabs -->
          <div class="bg-white rounded-4 shadow-sm border mb-4">
            <div class="nav-tabs-custom d-flex flex-wrap border-bottom p-2 gap-2">
              <button type="button" 
                      :class="['tab-btn', activeTab === 'points' ? 'active' : '']"
                      @click="activeTab = 'points'">
                <i class="fa-solid fa-coins text-warning me-2"></i> Mutasi & Histori Poin
              </button>
              <button type="button" 
                      :class="['tab-btn', activeTab === 'orders' ? 'active' : '']"
                      @click="activeTab = 'orders'">
                <i class="fa-solid fa-receipt text-primary me-2"></i> Histori Pesanan ({{ customer.orders?.length || 0 }})
              </button>
              <button type="button" 
                      :class="['tab-btn', activeTab === 'profile' ? 'active' : '']"
                      @click="activeTab = 'profile'">
                <i class="fa-solid fa-id-card text-success me-2"></i> Data Diri & Profil
              </button>
              <button type="button" 
                      :class="['tab-btn', activeTab === 'stores' ? 'active' : '']"
                      @click="activeTab = 'stores'">
                <i class="fa-solid fa-store text-danger me-2"></i> Toko Langganan
              </button>
            </div>

            <div class="p-4">
              <!-- ============================================== -->
              <!-- TAB 1: MUTASI & HISTORI CREDIT POINT          -->
              <!-- ============================================== -->
              <div v-if="activeTab === 'points'">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                  <div>
                    <h5 class="fw-bold mb-1"><i class="fa-solid fa-clock-rotate-left text-warning me-2"></i>Riwayat Transaksi Credit Point</h5>
                    <p class="text-muted small mb-0">Catatan perolehan dan pemakaian poin dari pesanan menu maupun promo eksklusif.</p>
                  </div>
                  <div class="badge bg-warning-subtle text-dark border border-warning px-3 py-2 rounded-pill font-mono">
                    Saldo Saat Ini: <strong>{{ formatNumber(customer.loyalty_points || 0) }} Pts</strong>
                  </div>
                </div>

                <!-- Explanation info banner -->
                <div class="p-3 mb-4 rounded-3 bg-light border d-flex gap-3 align-items-start">
                  <div class="text-warning fs-3 mt-1"><i class="fa-solid fa-circle-question"></i></div>
                  <div class="small text-muted">
                    <strong class="text-dark d-block mb-1">Bagaimana Cara Mendapatkan Credit Point?</strong>
                    Poin reward dapat diperoleh secara otomatis ketika Anda memesan menu yang memiliki tanda poin reward di toko langganan. 
                    Anda juga dapat memilih opsi <em>"Dapatkan Poin Lebih Banyak"</em> pada promo tertentu untuk melipatgandakan poin pesanan Anda!
                  </div>
                </div>

                <!-- Transaction List Table -->
                <div v-if="customer.point_transactions && customer.point_transactions.length > 0" class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small">
                      <tr>
                        <th>TANGGAL & WAKTU</th>
                        <th>TIPE</th>
                        <th>CABANG TOKO</th>
                        <th>KETERANGAN</th>
                        <th class="text-end">JUMLAH POIN</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="tx in customer.point_transactions" :key="tx.id">
                        <td class="small font-mono text-muted">
                          {{ formatDate(tx.created_at) }}
                        </td>
                        <td>
                          <span v-if="tx.type === 'earn'" class="badge bg-success-subtle text-success border border-success">
                            <i class="fa-solid fa-arrow-down-left me-1"></i> Reward Menu
                          </span>
                          <span v-else-if="tx.type === 'bonus'" class="badge bg-warning-subtle text-dark border border-warning">
                            <i class="fa-solid fa-gift me-1"></i> Bonus Promo
                          </span>
                          <span v-else-if="tx.type === 'redeem'" class="badge bg-danger-subtle text-danger border border-danger">
                            <i class="fa-solid fa-arrow-up-right me-1"></i> Pemakaian
                          </span>
                          <span v-else class="badge bg-secondary-subtle text-dark border">
                            <i class="fa-solid fa-sliders me-1"></i> Penyesuaian
                          </span>
                        </td>
                        <td class="small fw-semibold">
                          {{ tx.store?.name || 'Seluruh Cabang' }}
                        </td>
                        <td class="small">
                          {{ tx.description }}
                          <span v-if="tx.order_id" class="text-muted font-mono d-block" style="font-size: 0.76rem;">
                            Ref Order #{{ tx.order?.order_number || tx.order_id }}
                          </span>
                        </td>
                        <td class="text-end font-mono fw-bold fs-6" :class="tx.points >= 0 ? 'text-success' : 'text-danger'">
                          {{ tx.points >= 0 ? '+' : '' }}{{ formatNumber(tx.points) }} Pts
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Empty state -->
                <div v-else class="text-center py-5">
                  <div class="empty-icon text-muted mb-2">
                    <i class="fa-solid fa-coins fa-3x"></i>
                  </div>
                  <h6 class="fw-bold">Belum Ada Transaksi Poin</h6>
                  <p class="text-muted small mb-3">Pesan menu makanan favorit Anda untuk mulai mengumpulkan Credit Point!</p>
                  <Link href="/" class="btn btn-warning btn-sm fw-bold">
                    <i class="fa-solid fa-store me-1"></i> Pesan Sekarang
                  </Link>
                </div>
              </div>

              <!-- ============================================== -->
              <!-- TAB 2: HISTORI PESANAN (PURCHASE HISTORY)      -->
              <!-- ============================================== -->
              <div v-if="activeTab === 'orders'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div>
                    <h5 class="fw-bold mb-1"><i class="fa-solid fa-receipt text-primary me-2"></i>Histori Pembelian Makanan</h5>
                    <p class="text-muted small mb-0">Daftar semua pesanan yang pernah Anda lakukan di seluruh cabang Naumil Rice.</p>
                  </div>
                </div>

                <div v-if="customer.orders && customer.orders.length > 0" class="d-flex flex-column gap-3">
                  <div v-for="order in customer.orders" :key="order.id" class="order-history-card p-3 rounded-4 border bg-light shadow-sm">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2 pb-2 border-bottom">
                      <div>
                        <div class="d-flex align-items-center gap-2">
                          <span class="fw-bold font-mono text-primary fs-6">{{ order.order_number }}</span>
                          <span :class="['badge rounded-pill', getStatusBadgeClass(order.status)]">
                            {{ order.status }}
                          </span>
                        </div>
                        <small class="text-muted d-block mt-1">
                          <i class="fa-solid fa-calendar me-1"></i> {{ formatDate(order.created_at) }} &bull;
                          <i class="fa-solid fa-store ms-1 me-1"></i> <strong>{{ order.store?.name || 'Cabang' }}</strong>
                        </small>
                      </div>

                      <div class="text-end">
                        <span class="small text-muted d-block">Total Pembayaran:</span>
                        <strong class="fs-5 font-mono text-dark">Rp {{ formatNumber(order.total_amount) }}</strong>
                      </div>
                    </div>

                    <!-- Items List -->
                    <div class="order-items-list mb-3">
                      <div v-for="item in order.items" :key="item.id" class="d-flex justify-content-between py-1 small border-bottom border-dashed">
                        <div>
                          <strong>{{ item.menu_name || item.menu?.name }}</strong>
                          <span class="text-muted ms-1">x{{ item.quantity }}</span>
                          <span v-if="item.selected_options && item.selected_options.length" class="text-muted d-block" style="font-size: 0.78rem;">
                            + {{ formatOptions(item.selected_options) }}
                          </span>
                        </div>
                        <span class="font-mono text-muted">Rp {{ formatNumber(item.subtotal) }}</span>
                      </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-1">
                      <div>
                        <span v-if="order.points_earned > 0" class="badge bg-warning-subtle text-dark border border-warning">
                          <i class="fa-solid fa-coins text-warning me-1"></i> Reward Poin: +{{ order.points_earned }} Pts
                        </span>
                        <span v-else class="text-muted small">
                          Metode Bayar: <strong>{{ order.payment_method }}</strong>
                        </span>
                      </div>

                      <Link :href="'/trace/' + order.order_number" class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-location-crosshairs me-1"></i> Lacak Status Pesanan
                      </Link>
                    </div>
                  </div>
                </div>

                <!-- Empty state -->
                <div v-else class="text-center py-5">
                  <div class="empty-icon text-muted mb-2">
                    <i class="fa-solid fa-utensils fa-3x"></i>
                  </div>
                  <h6 class="fw-bold">Belum Ada Riwayat Pesanan</h6>
                  <p class="text-muted small mb-3">Anda belum memiliki pesanan makanan. Kunjungi cabang favorit Anda untuk mulai memesan!</p>
                  <Link href="/" class="btn btn-primary btn-sm fw-bold">
                    <i class="fa-solid fa-store me-1"></i> Jelajahi Menu Toko
                  </Link>
                </div>
              </div>

              <!-- ============================================== -->
              <!-- TAB 3: DATA DIRI & PROFIL PELANGGAN SETIA       -->
              <!-- ============================================== -->
              <div v-if="activeTab === 'profile'">
                <h5 class="fw-bold mb-1"><i class="fa-solid fa-id-card text-success me-2"></i>Informasi Akun & Data Diri</h5>
                <p class="text-muted small mb-4">Pastikan data kontak dan alamat pengiriman selalu akurat agar proses pesanan lancar.</p>

                <div v-if="profileUpdateSuccess" class="alert alert-success d-flex align-items-center gap-2 mb-4 py-2">
                  <i class="fa-solid fa-circle-check"></i>
                  <span>{{ profileUpdateSuccess }}</span>
                </div>

                <form @submit.prevent="submitProfileUpdate">
                  <div class="form-row-2 mb-3">
                    <div class="input-group-modern">
                      <label class="modern-label">
                        <span>Nama Lengkap <strong class="text-danger">*</strong></span>
                      </label>
                      <div class="modern-field-wrapper">
                        <i class="fa-solid fa-user field-icon"></i>
                        <input type="text" v-model="profileForm.name" class="modern-input" required />
                      </div>
                    </div>

                    <div class="input-group-modern">
                      <label class="modern-label">
                        <span>Jenis Kelamin <strong class="text-danger">*</strong></span>
                      </label>
                      <div class="gender-pill-group">
                        <label :class="['gender-pill', profileForm.gender === 'Laki-laki' ? 'active' : '']">
                          <input type="radio" value="Laki-laki" v-model="profileForm.gender" class="d-none" />
                          <i class="fa-solid fa-mars"></i>
                          <span>Laki-laki</span>
                        </label>
                        <label :class="['gender-pill', profileForm.gender === 'Perempuan' ? 'active' : '']">
                          <input type="radio" value="Perempuan" v-model="profileForm.gender" class="d-none" />
                          <i class="fa-solid fa-venus"></i>
                          <span>Perempuan</span>
                        </label>
                      </div>
                    </div>
                  </div>

                  <div class="form-row-2 mb-3">
                    <div class="input-group-modern">
                      <label class="modern-label">
                        <span>Email Valid (Terdaftar)</span>
                      </label>
                      <div class="modern-field-wrapper">
                        <i class="fa-solid fa-envelope field-icon"></i>
                        <input type="email" :value="customer.email" class="modern-input is-disabled" disabled />
                        <span class="verified-badge-pill">
                          <i class="fa-solid fa-circle-check text-success me-1"></i> Terverifikasi
                        </span>
                      </div>
                      <small class="text-muted d-block mt-1" style="font-size: 0.76rem;">Email utama tidak dapat diubah untuk keamanan poin.</small>
                    </div>

                    <div class="input-group-modern">
                      <label class="modern-label">
                        <span>Nomor HP / WhatsApp <strong class="text-danger">*</strong></span>
                      </label>
                      <div class="modern-field-wrapper">
                        <i class="fa-brands fa-whatsapp field-icon text-success"></i>
                        <input type="text" v-model="profileForm.whatsapp" class="modern-input font-mono" required />
                      </div>
                    </div>
                  </div>

                  <div class="form-row-2 mb-3">
                    <div class="input-group-modern">
                      <label class="modern-label">
                        <span>Pekerjaan Saat Ini <strong class="text-danger">*</strong></span>
                      </label>
                      <div class="modern-field-wrapper">
                        <i class="fa-solid fa-briefcase field-icon"></i>
                        <input type="text" v-model="profileForm.occupation" class="modern-input" placeholder="Contoh: Karyawan Swasta, Pengusaha..." required />
                      </div>
                    </div>

                    <div class="input-group-modern">
                      <label class="modern-label">
                        <span>Alamat Pengiriman Lengkap <strong class="text-danger">*</strong></span>
                      </label>
                      <div class="modern-field-wrapper textarea-wrapper">
                        <i class="fa-solid fa-map-location-dot field-icon textarea-icon"></i>
                        <textarea v-model="profileForm.address" rows="2" class="modern-input modern-textarea" placeholder="Nama Jalan, No. Rumah, RT/RW, Patokan..." required></textarea>
                      </div>
                    </div>
                  </div>

                  <div class="text-end pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4 fw-bold" :disabled="savingProfile">
                      <i v-if="savingProfile" class="fa-solid fa-spinner fa-spin me-1"></i>
                      <i v-else class="fa-solid fa-floppy-disk me-1"></i>
                      <span>{{ savingProfile ? 'Menyimpan...' : 'Simpan Perubahan Profil' }}</span>
                    </button>
                  </div>
                </form>
              </div>

              <!-- ============================================== -->
              <!-- TAB 4: CABANG TOKO LANGGANAN                  -->
              <!-- ============================================== -->
              <div v-if="activeTab === 'stores'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div>
                    <h5 class="fw-bold mb-1"><i class="fa-solid fa-store text-danger me-2"></i>Pilihan Cabang Toko Langganan</h5>
                    <p class="text-muted small mb-0">Pilih cabang mana saja yang ingin Anda jadikan toko langganan. Anda dapat memilih lebih dari satu cabang.</p>
                  </div>
                </div>

                <div v-if="storeUpdateSuccess" class="alert alert-success d-flex align-items-center gap-2 mb-3 py-2">
                  <i class="fa-solid fa-circle-check"></i>
                  <span>{{ storeUpdateSuccess }}</span>
                </div>

                <div class="row g-3 mb-4">
                  <div v-for="st in allStores" :key="st.id" class="col-md-6">
                    <label :class="['store-choice-card p-3 rounded-4 border d-flex gap-3 align-items-start cursor-pointer h-100', selectedStoreIds.includes(st.id) ? 'selected border-primary bg-primary-subtle' : 'bg-light']">
                      <input type="checkbox" :value="st.id" v-model="selectedStoreIds" class="form-check-input mt-1" />
                      <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                          <strong class="fs-6">{{ st.name }}</strong>
                          <span class="badge bg-secondary-subtle text-dark font-mono">{{ st.code }}</span>
                        </div>
                        <p class="small text-muted mb-2">{{ st.address }}</p>
                        <div class="d-flex gap-2">
                          <Link :href="'/' + (st.slug || st.code)" class="btn btn-sm btn-outline-secondary py-0" style="font-size: 0.78rem;">
                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Buka Menu Cabang
                          </Link>
                        </div>
                      </div>
                    </label>
                  </div>
                </div>

                <div class="text-end pt-3 border-top">
                  <button type="button" class="btn btn-danger px-4 fw-bold" @click="submitStoresUpdate" :disabled="savingStores">
                    <i v-if="savingStores" class="fa-solid fa-spinner fa-spin me-1"></i>
                    <i v-else class="fa-solid fa-check-double me-1"></i>
                    <span>{{ savingStores ? 'Memperbarui...' : 'Simpan Pilihan Toko Langganan' }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- SCENARIO B: GUEST / NOT LOGGED IN YET                    -->
        <!-- ======================================================== -->
        <div v-else class="loyalty-guest-portal max-w-800 mx-auto">
          <!-- Hero Section -->
          <div class="text-center mb-4">
            <div class="guest-crown-bubble mb-3 mx-auto">
              <i class="fa-solid fa-crown text-warning fa-2x"></i>
            </div>
            <h2 class="fw-bold mb-2">Portal Pelanggan Setia Naumil Rice</h2>
            <p class="text-muted">
              Daftar sebagai Pelanggan Setia untuk kumpulkan <strong>Credit Points</strong> di setiap pembelian menu, nikmati <strong>diskon khusus</strong>, dan promo eksklusif cabang langganan Anda!
            </p>
          </div>

          <!-- Benefits Highlight: 1 row on Desktop (3 columns side by side), stacked on mobile -->
          <div class="benefits-grid mb-4">
            <div class="benefit-card">
              <div class="benefit-icon-bubble bg-amber-soft text-amber">
                <i class="fa-solid fa-coins fa-xl"></i>
              </div>
              <div class="benefit-body">
                <h6 class="benefit-title">Credit Point</h6>
                <p class="benefit-desc">Kumpulkan poin di setiap porsi menu untuk ditukar potongan harga.</p>
              </div>
            </div>
            <div class="benefit-card">
              <div class="benefit-icon-bubble bg-rose-soft text-rose">
                <i class="fa-solid fa-percent fa-xl"></i>
              </div>
              <div class="benefit-body">
                <h6 class="benefit-title">Promo Eksklusif</h6>
                <p class="benefit-desc">Diskon presentase all-menu atau potongan belanja spesial member.</p>
              </div>
            </div>
            <div class="benefit-card">
              <div class="benefit-icon-bubble bg-blue-soft text-blue">
                <i class="fa-solid fa-store fa-xl"></i>
              </div>
              <div class="benefit-body">
                <h6 class="benefit-title">Multi Cabang Toko</h6>
                <p class="benefit-desc">Pilih dan ikuti lebih dari satu cabang toko favorit Anda.</p>
              </div>
            </div>
          </div>

          <!-- Auth Box Card -->
          <div class="auth-card-modern shadow-sm">
            <!-- Modern Segmented Tabs: Login vs Register -->
            <div class="auth-segmented-tabs mb-4">
              <button type="button" 
                      :class="['segmented-tab-btn', authTab === 'login' ? 'active' : '']"
                      @click="authTab = 'login'">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Masuk Akun
              </button>
              <button type="button" 
                      :class="['segmented-tab-btn', authTab === 'register' ? 'active' : '']"
                      @click="authTab = 'register'">
                <i class="fa-solid fa-user-plus me-2"></i> Daftar Pelanggan Baru
              </button>
            </div>

            <!-- SUB-VIEW 1: LOGIN -->
            <div v-if="authTab === 'login'">
              <div v-if="loginError" class="modern-alert alert-error mb-4">
                <i class="fa-solid fa-triangle-exclamation alert-icon"></i>
                <div class="alert-text">{{ loginError }}</div>
              </div>

              <form @submit.prevent="submitLogin">
                <div class="input-group-modern mb-3">
                  <label class="modern-label">
                    <span>Email Terdaftar <strong class="text-danger">*</strong></span>
                  </label>
                  <div class="modern-field-wrapper">
                    <i class="fa-solid fa-envelope field-icon"></i>
                    <input type="email" 
                           v-model="loginForm.email" 
                           class="modern-input" 
                           placeholder="contoh: naufal@gmail.com" 
                           required />
                  </div>
                </div>

                <div class="input-group-modern mb-4">
                  <label class="modern-label">
                    <span>Password Login <strong class="text-danger">*</strong></span>
                  </label>
                  <div class="modern-field-wrapper">
                    <i class="fa-solid fa-lock field-icon"></i>
                    <input :type="showPassword ? 'text' : 'password'" 
                           v-model="loginForm.password" 
                           class="modern-input has-toggle" 
                           placeholder="Masukkan password Anda" 
                           required />
                    <button type="button" class="field-toggle-btn" @click="showPassword = !showPassword" tabindex="-1">
                      <i :class="['fa-solid', showPassword ? 'fa-eye-slash' : 'fa-eye']"></i>
                    </button>
                  </div>
                </div>

                <button type="submit" class="btn-modern-submit" :disabled="loggingIn">
                  <i v-if="loggingIn" class="fa-solid fa-spinner fa-spin"></i>
                  <i v-else class="fa-solid fa-right-to-bracket"></i>
                  <span>{{ loggingIn ? 'Memverifikasi Akun...' : 'Masuk ke Akun Pelanggan Setia' }}</span>
                </button>
              </form>

              <!-- Resend activation helper -->
              <div v-if="showResendActivation" class="mt-4 p-3 bg-light rounded-4 border text-center">
                <small class="text-muted d-block mb-2">Belum menerima email aktivasi verifikasi akun?</small>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="handleResendActivation" :disabled="resendingActivation">
                  <i v-if="resendingActivation" class="fa-solid fa-spinner fa-spin me-1"></i>
                  <i v-else class="fa-solid fa-envelope me-1"></i>
                  <span>Kirim Ulang Link Aktivasi</span>
                </button>
              </div>
            </div>

            <!-- SUB-VIEW 2: REGISTER -->
            <div v-else>
              <!-- Verification link sent success state -->
              <div v-if="regSuccessMsg" class="verification-success-box text-center p-4 rounded-4 bg-success-subtle border border-success">
                <div class="success-icon-bubble mx-auto mb-3">
                  <i class="fa-solid fa-envelope-circle-check text-success fa-2x"></i>
                </div>
                <h5 class="fw-bold text-success mb-2">Link Verifikasi Telah Dikirim!</h5>
                <p class="small text-muted mb-3">{{ regSuccessMsg }}</p>
                <p class="small text-muted mb-4">
                  Silakan buka email Anda dan klik tautan verifikasi. Setelah itu Anda dapat masuk dan menikmati semua promo & poin!
                </p>
                <button type="button" class="btn-modern-submit" @click="authTab = 'login'">
                  <i class="fa-solid fa-right-to-bracket me-2"></i> Menuju Form Login
                </button>
              </div>

              <!-- Registration Inputs Form -->
              <form v-else @submit.prevent="submitRegister">
                <div v-if="regError" class="modern-alert alert-error mb-4">
                  <i class="fa-solid fa-triangle-exclamation alert-icon"></i>
                  <div class="alert-text">{{ regError }}</div>
                </div>

                <div class="form-row-2 mb-3">
                  <div class="input-group-modern">
                    <label class="modern-label">
                      <span>Nama Lengkap <strong class="text-danger">*</strong></span>
                    </label>
                    <div class="modern-field-wrapper">
                      <i class="fa-solid fa-user field-icon"></i>
                      <input type="text" v-model="regForm.name" class="modern-input" placeholder="Contoh: Muhammad Naufal" required />
                    </div>
                  </div>

                  <div class="input-group-modern">
                    <label class="modern-label">
                      <span>Jenis Kelamin <strong class="text-danger">*</strong></span>
                    </label>
                    <div class="gender-pill-group">
                      <label :class="['gender-pill', regForm.gender === 'Laki-laki' ? 'active' : '']">
                        <input type="radio" value="Laki-laki" v-model="regForm.gender" class="d-none" />
                        <i class="fa-solid fa-mars"></i>
                        <span>Laki-laki</span>
                      </label>
                      <label :class="['gender-pill', regForm.gender === 'Perempuan' ? 'active' : '']">
                        <input type="radio" value="Perempuan" v-model="regForm.gender" class="d-none" />
                        <i class="fa-solid fa-venus"></i>
                        <span>Perempuan</span>
                      </label>
                    </div>
                  </div>
                </div>

                <div class="form-row-2 mb-3">
                  <div class="input-group-modern">
                    <label class="modern-label">
                      <span>Email Valid <strong class="text-danger">*</strong></span>
                    </label>
                    <div class="modern-field-wrapper">
                      <i class="fa-solid fa-envelope field-icon"></i>
                      <input type="email" v-model="regForm.email" class="modern-input" placeholder="nama@gmail.com" required />
                    </div>
                  </div>

                  <div class="input-group-modern">
                    <label class="modern-label">
                      <span>Password Login <strong class="text-danger">*</strong></span>
                    </label>
                    <div class="modern-field-wrapper">
                      <i class="fa-solid fa-lock field-icon"></i>
                      <input :type="showRegPassword ? 'text' : 'password'" 
                             v-model="regForm.password" 
                             class="modern-input has-toggle" 
                             placeholder="Minimal 6 karakter" 
                             required />
                      <button type="button" class="field-toggle-btn" @click="showRegPassword = !showRegPassword" tabindex="-1">
                        <i :class="['fa-solid', showRegPassword ? 'fa-eye-slash' : 'fa-eye']"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="form-row-2 mb-3">
                  <div class="input-group-modern">
                    <label class="modern-label">
                      <span>Nomor HP / WhatsApp <strong class="text-danger">*</strong></span>
                    </label>
                    <div class="modern-field-wrapper">
                      <i class="fa-brands fa-whatsapp field-icon text-success"></i>
                      <input type="tel" v-model="regForm.whatsapp" class="modern-input font-mono" placeholder="081298765432" required />
                    </div>
                  </div>

                  <div class="input-group-modern">
                    <label class="modern-label">
                      <span>Pekerjaan Saat Ini <strong class="text-danger">*</strong></span>
                    </label>
                    <div class="modern-field-wrapper">
                      <i class="fa-solid fa-briefcase field-icon"></i>
                      <input type="text" v-model="regForm.occupation" class="modern-input" placeholder="Contoh: Karyawan Swasta" required />
                    </div>
                  </div>
                </div>

                <!-- Multi-store Selection -->
                <div class="input-group-modern mb-3">
                  <label class="modern-label">
                    <span><i class="fa-solid fa-store text-danger me-1"></i> Pilih Cabang Toko Langganan</span>
                    <span class="text-muted small fw-normal">(Bisa pilih lebih dari satu)</span>
                  </label>
                  <div class="modern-store-selection-box">
                    <label v-for="st in allStores" :key="st.id" 
                           :class="['store-select-item', regForm.store_ids.includes(st.id) ? 'selected' : '']">
                      <input type="checkbox" :value="st.id" v-model="regForm.store_ids" class="modern-checkbox" />
                      <div class="store-item-info">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                          <strong class="store-item-name">{{ st.name }}</strong>
                          <span class="store-item-code">{{ st.code }}</span>
                        </div>
                        <p class="store-item-address">{{ st.address }}</p>
                      </div>
                    </label>
                  </div>
                </div>

                <div class="input-group-modern mb-4">
                  <label class="modern-label">
                    <span>Alamat Pengiriman Lengkap <strong class="text-danger">*</strong></span>
                  </label>
                  <div class="modern-field-wrapper textarea-wrapper">
                    <i class="fa-solid fa-map-location-dot field-icon textarea-icon"></i>
                    <textarea v-model="regForm.address" rows="3" class="modern-input modern-textarea" placeholder="Nama Jalan, No. Rumah, RT/RW, Patokan..." required></textarea>
                  </div>
                </div>

                <button type="submit" class="btn-modern-submit btn-submit-gold" :disabled="registering">
                  <i v-if="registering" class="fa-solid fa-spinner fa-spin"></i>
                  <i v-else class="fa-solid fa-paper-plane"></i>
                  <span>{{ registering ? 'Mendaftarkan Akun...' : 'Daftar & Kirim Email Verifikasi' }}</span>
                </button>
              </form>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  initialClient: {
    type: Object,
    default: null
  },
  allStores: {
    type: Array,
    default: () => []
  },
  activePromos: {
    type: Array,
    default: () => []
  }
})

const customer = ref(props.initialClient)
const isLoggedIn = computed(() => !!customer.value && !!customer.value.id)

// Tabs inside dashboard
const activeTab = ref('points')

// Guest Portal Auth Tabs
const authTab = ref('login') // 'login' or 'register'
const loggingIn = ref(false)
const loginError = ref('')
const showResendActivation = ref(false)
const resendingActivation = ref(false)

const loginForm = ref({
  email: '',
  password: ''
})

const showPassword = ref(false)
const showRegPassword = ref(false)

const registering = ref(false)
const regError = ref('')
const regSuccessMsg = ref('')

const regForm = ref({
  name: '',
  gender: 'Laki-laki',
  email: '',
  password: '',
  whatsapp: '',
  occupation: 'Karyawan Swasta',
  address: '',
  store_ids: props.allStores.length > 0 ? [props.allStores[0].id] : []
})

// Profile Form Edit
const savingProfile = ref(false)
const profileUpdateSuccess = ref('')
const profileForm = ref({
  name: '',
  gender: 'Laki-laki',
  whatsapp: '',
  occupation: '',
  address: ''
})

// Stores Form Edit
const savingStores = ref(false)
const storeUpdateSuccess = ref('')
const selectedStoreIds = ref([])

const totalEarnedPoints = computed(() => {
  if (!customer.value?.point_transactions) return 0
  return customer.value.point_transactions
    .filter(tx => tx.points > 0)
    .reduce((sum, tx) => sum + tx.points, 0)
})

const syncCustomerData = (clientData) => {
  customer.value = clientData
  if (clientData) {
    profileForm.value = {
      name: clientData.name || '',
      gender: clientData.gender || 'Laki-laki',
      whatsapp: clientData.whatsapp || '',
      occupation: clientData.occupation || '',
      address: clientData.address || ''
    }
    selectedStoreIds.value = (clientData.stores || []).map(s => s.id)
    localStorage.setItem('naumil_customer_session', JSON.stringify(clientData))
    localStorage.setItem('naumil_user', JSON.stringify(clientData))
  }
}

const fetchProfileData = async () => {
  try {
    const res = await axios.get('/api/v1/customer/profile-data')
    if (res.data.success && res.data.client) {
      syncCustomerData(res.data.client)
    }
  } catch (err) {
    // Check local storage fallback
    const cached = localStorage.getItem('naumil_customer_session')
    if (cached) {
      try {
        const client = JSON.parse(cached)
        if (client.email) {
          const res2 = await axios.get(`/api/v1/customer/profile-data?email=${encodeURIComponent(client.email)}`)
          if (res2.data.success && res2.data.client) {
            syncCustomerData(res2.data.client)
          }
        }
      } catch (e) {}
    }
  }
}

const submitLogin = async () => {
  try {
    loggingIn.value = true
    loginError.value = ''
    showResendActivation.value = false

    const res = await axios.post('/api/v1/customer/login-loyalty', loginForm.value)
    if (res.data.success) {
      syncCustomerData(res.data.client)
      await fetchProfileData()
    }
  } catch (err) {
    loginError.value = err.response?.data?.message || 'Login gagal. Periksa kembali email dan password Anda.'
    if (err.response?.data?.is_unverified) {
      showResendActivation.value = true
    }
  } finally {
    loggingIn.value = false
  }
}

const handleResendActivation = async () => {
  if (!loginForm.value.email) return
  try {
    resendingActivation.value = true
    const res = await axios.post('/api/v1/customer/resend-activation', { email: loginForm.value.email })
    alert(res.data.message || 'Link aktivasi baru telah dikirimkan ke email Anda.')
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal mengirim ulang aktivasi.')
  } finally {
    resendingActivation.value = false
  }
}

const submitRegister = async () => {
  try {
    registering.value = true
    regError.value = ''
    regSuccessMsg.value = ''

    const res = await axios.post('/api/v1/customer/register-loyalty', regForm.value)
    if (res.data.success) {
      regSuccessMsg.value = res.data.message
    }
  } catch (err) {
    regError.value = err.response?.data?.message || 'Gagal mendaftarkan akun. Silakan periksa formulir.'
  } finally {
    registering.value = false
  }
}

const submitProfileUpdate = async () => {
  try {
    savingProfile.value = true
    profileUpdateSuccess.value = ''

    const res = await axios.post('/api/v1/customer/update-profile', {
      ...profileForm.value,
      client_id: customer.value.id
    })

    if (res.data.success) {
      profileUpdateSuccess.value = res.data.message
      syncCustomerData(res.data.client)
      setTimeout(() => {
        profileUpdateSuccess.value = ''
      }, 3500)
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal memperbarui profil.')
  } finally {
    savingProfile.value = false
  }
}

const submitStoresUpdate = async () => {
  try {
    savingStores.value = true
    storeUpdateSuccess.value = ''

    const res = await axios.post('/api/v1/customer/update-stores', {
      store_ids: selectedStoreIds.value,
      client_id: customer.value.id
    })

    if (res.data.success) {
      storeUpdateSuccess.value = res.data.message
      await fetchProfileData()
      setTimeout(() => {
        storeUpdateSuccess.value = ''
      }, 3500)
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal memperbarui toko langganan.')
  } finally {
    savingStores.value = false
  }
}

const handleLogout = async () => {
  if (!confirm('Apakah Anda yakin ingin keluar dari akun Pelanggan Setia?')) return

  try {
    await axios.post('/api/v1/customer/logout')
  } catch (e) {}

  localStorage.removeItem('naumil_customer_session')
  localStorage.removeItem('naumil_user')
  customer.value = null
  router.visit('/')
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

const formatOptions = (options) => {
  if (!options) return ''
  if (Array.isArray(options)) {
    return options.map(o => o.name || o).join(', ')
  }
  return ''
}

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'Order Ready': return 'bg-success text-white'
    case 'Order On Delivery': return 'bg-info text-dark'
    case 'Order On Packaging': return 'bg-warning text-dark'
    case 'Order On Proses': return 'bg-primary text-white'
    case 'Order Reject': return 'bg-danger text-white'
    default: return 'bg-secondary text-white'
  }
}

onMounted(() => {
  if (props.initialClient) {
    syncCustomerData(props.initialClient)
  } else {
    fetchProfileData()
  }
})
</script>

<style scoped>
.customer-profile-page {
  background-color: #f8fafc;
  min-height: calc(100vh - 160px);
}

.max-w-800 {
  max-width: 780px;
}

/* Membership VIP Card */
.membership-vip-card {
  background: linear-gradient(135deg, #881337 0%, #be123c 50%, #4c0519 100%);
  color: white;
  border-radius: 20px;
  position: relative;
  overflow: hidden;
  padding: 2rem;
  border: 1px solid rgba(251, 191, 36, 0.3);
}

.vip-pattern-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image: radial-gradient(circle at 80% 20%, rgba(251, 191, 36, 0.18) 0%, transparent 60%);
  pointer-events: none;
}

.vip-card-content {
  position: relative;
  z-index: 1;
}

.badge-vip {
  background: rgba(251, 191, 36, 0.2);
  color: #fbbf24;
  border: 1px solid rgba(251, 191, 36, 0.4);
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.05em;
}

.badge-verified {
  background: rgba(34, 197, 94, 0.2);
  color: #4ade80;
  border: 1px solid rgba(34, 197, 94, 0.4);
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.76rem;
  font-weight: 600;
}

.vip-name {
  font-family: var(--font-display, inherit);
  font-weight: 800;
  font-size: 1.85rem;
}

.points-display-box {
  background: rgba(0, 0, 0, 0.35);
  border: 1.5px solid rgba(251, 191, 36, 0.4);
  min-width: 220px;
  backdrop-filter: blur(8px);
}

/* Tabs */
.tab-btn {
  background: transparent;
  border: none;
  font-size: 0.92rem;
  font-weight: 700;
  color: #64748b;
  padding: 10px 18px;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.tab-btn:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.tab-btn.active {
  background: #fff1f2;
  color: #be123c;
}

/* Store choice card in Dashboard */
.store-choice-card {
  transition: all 0.2s ease;
}

.store-choice-card:hover {
  border-color: #cbd5e1;
}

.store-choice-card.selected {
  border-color: #be123c !important;
  background: #fff1f2 !important;
}

.guest-crown-bubble {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: #fef3c7;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #fde68a;
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
}

/* ======================================================== */
/* 1. BENEFITS HIGHLIGHT GRID (1 ROW ON DESKTOP, STACK ON HP)*/
/* ======================================================== */
.benefits-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  margin-bottom: 2rem;
}

@media (min-width: 768px) {
  .benefits-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
  }
}

.benefit-card {
  background: #ffffff;
  border: 1.5px solid #f1f5f9;
  border-radius: 18px;
  padding: 1.5rem 1.25rem;
  text-align: center;
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.03);
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  display: flex;
  flex-direction: column;
  align-items: center;
}

.benefit-card:hover {
  transform: translateY(-3px);
  border-color: #fecdd3;
  box-shadow: 0 10px 25px rgba(225, 29, 72, 0.08);
}

.benefit-icon-bubble {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.9rem;
}

.bg-amber-soft { background: #fef3c7; color: #d97706; }
.bg-rose-soft { background: #ffe4e6; color: #e11d48; }
.bg-blue-soft { background: #e0f2fe; color: #0284c7; }

.benefit-title {
  font-weight: 800;
  font-size: 1.05rem;
  color: #0f172a;
  margin-bottom: 0.35rem;
}

.benefit-desc {
  font-size: 0.84rem;
  color: #64748b;
  margin: 0;
  line-height: 1.45;
}

/* ======================================================== */
/* 2. MODERN AUTH CARD & SEGMENTED TABS                    */
/* ======================================================== */
.auth-card-modern {
  background: #ffffff;
  border-radius: 22px;
  padding: 2.25rem 2.25rem;
  border: 1.5px solid #f1f5f9;
  box-shadow: 0 12px 35px rgba(0, 0, 0, 0.05);
}

.auth-segmented-tabs {
  background: #f1f5f9;
  padding: 6px;
  border-radius: 14px;
  display: flex;
  gap: 6px;
}

.segmented-tab-btn {
  flex: 1;
  padding: 0.85rem 1rem;
  border: none;
  background: transparent;
  color: #64748b;
  font-weight: 700;
  font-size: 0.94rem;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  justify-content: center;
}

.segmented-tab-btn:hover {
  color: #0f172a;
}

.segmented-tab-btn.active {
  background: #ffffff;
  color: #be123c;
  font-weight: 800;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
}

/* ======================================================== */
/* 3. MODERN FORM INPUTS, LABELS & WRAPPERS                */
/* ======================================================== */
.form-row-2 {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

@media (min-width: 640px) {
  .form-row-2 {
    grid-template-columns: 1fr 1fr;
  }
}

.input-group-modern {
  display: flex;
  flex-direction: column;
}

.modern-label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.86rem;
  font-weight: 700;
  color: #334155;
  margin-bottom: 0.45rem;
}

.modern-field-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
}

.field-icon {
  position: absolute;
  left: 1.1rem;
  color: #94a3b8;
  font-size: 1.05rem;
  pointer-events: none;
  transition: color 0.2s ease;
  z-index: 2;
}

.modern-input {
  width: 100%;
  padding: 0.85rem 1.1rem 0.85rem 2.85rem;
  font-size: 0.95rem;
  font-family: inherit;
  font-weight: 500;
  color: #0f172a;
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  outline: none;
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}

.modern-input::placeholder {
  color: #94a3b8;
  font-weight: 400;
}

.modern-input:focus {
  background: #ffffff;
  border-color: #e11d48;
  box-shadow: 0 0 0 4px rgba(225, 29, 72, 0.12);
}

.modern-field-wrapper:focus-within .field-icon {
  color: #e11d48;
}

.modern-input.has-toggle {
  padding-right: 2.85rem;
}

.field-toggle-btn {
  position: absolute;
  right: 0.85rem;
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 6px;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: color 0.2s;
  z-index: 2;
}

.field-toggle-btn:hover {
  color: #0f172a;
}

.textarea-wrapper {
  align-items: flex-start;
}

.textarea-icon {
  top: 1rem;
}

.modern-textarea {
  min-height: 85px;
  resize: vertical;
  line-height: 1.5;
}

.modern-input.is-disabled {
  background: #f1f5f9;
  color: #64748b;
  cursor: not-allowed;
}

.verified-badge-pill {
  position: absolute;
  right: 0.85rem;
  background: #dcfce7;
  color: #15803d;
  font-size: 0.76rem;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 20px;
  pointer-events: none;
}

/* Chrome autofill background cleanup */
.modern-input:-webkit-autofill,
.modern-input:-webkit-autofill:hover, 
.modern-input:-webkit-autofill:focus {
  -webkit-box-shadow: 0 0 0px 1000px #ffffff inset !important;
  -webkit-text-fill-color: #0f172a !important;
  transition: background-color 5000s ease-in-out 0s;
}

/* ======================================================== */
/* 4. GENDER PILL SELECTOR                                  */
/* ======================================================== */
.gender-pill-group {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  height: 48px;
}

.gender-pill {
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 0.92rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
  user-select: none;
}

.gender-pill:hover {
  border-color: #cbd5e1;
  background: #ffffff;
}

.gender-pill.active {
  border-color: #e11d48;
  background: #fff1f2;
  color: #be123c;
  font-weight: 800;
  box-shadow: 0 2px 8px rgba(225, 29, 72, 0.15);
}

/* ======================================================== */
/* 5. STORE MULTI-SELECTION LIST                           */
/* ======================================================== */
.modern-store-selection-box {
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 14px;
  padding: 0.6rem;
  max-height: 150px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.store-select-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 8px 12px;
  border-radius: 10px;
  background: #ffffff;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.15s ease;
}

.store-select-item:hover {
  border-color: #e2e8f0;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

.store-select-item.selected {
  background: #fff1f2;
  border-color: #fecdd3;
}

.modern-checkbox {
  width: 19px;
  height: 19px;
  margin-top: 3px;
  accent-color: #e11d48;
  cursor: pointer;
}

.store-item-info {
  flex: 1;
}

.store-item-name {
  font-size: 0.88rem;
  color: #0f172a;
}

.store-item-code {
  font-size: 0.74rem;
  font-family: var(--font-mono, monospace);
  background: #f1f5f9;
  color: #475569;
  padding: 2px 6px;
  border-radius: 6px;
}

.store-item-address {
  font-size: 0.78rem;
  color: #64748b;
  margin: 0;
  line-height: 1.35;
}

/* ======================================================== */
/* 6. MODERN SUBMIT BUTTONS & ALERTS                       */
/* ======================================================== */
.btn-modern-submit {
  width: 100%;
  padding: 0.92rem 1.5rem;
  font-size: 1rem;
  font-weight: 800;
  color: white;
  background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
  border: none;
  border-radius: 14px;
  cursor: pointer;
  box-shadow: 0 4px 16px rgba(225, 29, 72, 0.3);
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.btn-modern-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(225, 29, 72, 0.4);
  background: linear-gradient(135deg, #be123c 0%, #9f1239 100%);
}

.btn-modern-submit:disabled {
  opacity: 0.75;
  cursor: not-allowed;
  transform: none;
}

.btn-submit-gold {
  background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
  box-shadow: 0 4px 16px rgba(217, 119, 6, 0.3);
}

.btn-submit-gold:hover:not(:disabled) {
  background: linear-gradient(135deg, #b45309 0%, #92400e 100%);
  box-shadow: 0 8px 24px rgba(217, 119, 6, 0.4);
}

.btn-resend-link {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #475569;
  padding: 6px 14px;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-resend-link:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.modern-alert {
  padding: 0.85rem 1.1rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 0.88rem;
}

.alert-error {
  background: #fff1f2;
  border: 1px solid #fecdd3;
  color: #be123c;
}

.alert-icon {
  font-size: 1.1rem;
  flex-shrink: 0;
}

.alert-text {
  flex: 1;
  font-weight: 600;
}

.success-icon-bubble {
  width: 68px;
  height: 68px;
  border-radius: 50%;
  background: #dcfce7;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #86efac;
}

@media (max-width: 768px) {
  .auth-card-modern {
    padding: 1.35rem 1.15rem;
  }
  .membership-vip-card {
    padding: 1.25rem;
  }
  .vip-name {
    font-size: 1.4rem;
  }
  .points-display-box {
    width: 100%;
    margin-top: 1rem;
  }
  .tab-btn {
    width: 100%;
    text-align: left;
  }
}
</style>
