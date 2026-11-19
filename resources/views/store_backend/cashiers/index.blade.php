@extends('store_backend.layouts.app')

@section('title', 'Hak Akses Kasir')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1 text-dark">
            <i class="fa-solid fa-user-tag text-primary me-2"></i>Hak Akses Kasir (POS)
        </h3>
        <p class="text-muted small mb-0">
            Kelola akun kasir khusus untuk cabang <strong>{{ $store->name }}</strong>. Akun kasir hanya dapat membuka Mode Kasir (menu aktif) & KDS ringkas (Packaging &rarr; Done), serta dapat login di Web maupun Aplikasi Android.
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('store.backend.cashier.pos', $store->slug) }}" class="btn btn-outline-primary fw-semibold">
            <i class="fa-solid fa-cash-register me-1"></i> Buka Mode Kasir
        </a>
        <button type="button" class="btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAddCashier">
            <i class="fa-solid fa-user-plus me-1"></i> Tambah Akun Kasir
        </button>
    </div>
</div>

{{-- Info Login URL & Aturan Akses --}}
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card-custom mb-0 h-100">
            <div class="p-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <div class="small fw-bold text-uppercase text-muted mb-1">
                        <i class="fa-solid fa-link me-1 text-primary"></i> URL Login Kasir (Web)
                    </div>
                    <code class="fs-6 text-primary" id="cashierLoginUrl">{{ route('store.backend.login', $store->slug) }}</code>
                    <div class="small text-muted mt-1">
                        Kasir juga dapat langsung login menggunakan username & password yang sama di <strong>Aplikasi Android Naumil Rice</strong>.
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyCashierUrl()">
                    <i class="fa-regular fa-copy me-1"></i> Salin URL
                </button>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card-custom mb-0 h-100 bg-light">
            <div class="p-3">
                <div class="small fw-bold text-uppercase text-muted mb-1">
                    <i class="fa-solid fa-shield-halved me-1 text-success"></i> Hak Akses & Notifikasi Kasir
                </div>
                <ul class="small text-secondary mb-0 ps-3">
                    <li>Hanya menampilkan <strong>Menu Aktif</strong> &amp; KDS (<strong>Packaging &rarr; Done</strong>).</li>
                    <li>Checkout tanpa isi data pelanggan: <strong>Cash</strong> (klik Lunas) atau <strong>QRIS Midtrans</strong>.</li>
                    <li>Notifikasi push pembelian/pembayaran hanya dikirim ke <strong>Admin Toko</strong> (bukan Kasir).</li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Tabel Daftar Kasir --}}
<div class="card-custom">
    <div class="card-custom-header">
        <h5 class="card-custom-title">
            <i class="fa-solid fa-users me-2 text-primary"></i>Daftar Akun Kasir ({{ $cashiers->count() }})
        </h5>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
            Cabang {{ $store->name }}
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Nama Kasir</th>
                    <th>Username Login</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Dibuat</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cashiers as $cashier)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                                     style="width:36px;height:36px;background:{{ $cashier->isActiveAccount() ? '#7c3aed' : '#94a3b8' }};">
                                    {{ strtoupper(substr($cashier->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">{{ $cashier->name }}</div>
                                    <span class="badge rounded-pill" style="background:#ede9fe;color:#5b21b6;font-size:.68rem;">
                                        <i class="fa-solid fa-cash-register me-1"></i>KASIR
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="font-mono fw-semibold text-primary">@<span>{{ $cashier->username }}</span></span>
                        </td>
                        <td class="small text-muted">{{ $cashier->email }}</td>
                        <td>
                            @if($cashier->isActiveAccount())
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="fa-solid fa-circle-check me-1"></i>Aktif
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                    <i class="fa-solid fa-ban me-1"></i>Nonaktif
                                </span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $cashier->created_at?->locale('id')->isoFormat('D MMM YYYY') }}</td>
                        <td class="text-end pe-4">
                            @php
                                $cashierPayload = [
                                    'id' => $cashier->id,
                                    'name' => $cashier->name,
                                    'username' => $cashier->username,
                                    'email' => str_ends_with((string) $cashier->email, '@naumilrice.local') ? '' : $cashier->email,
                                    'is_active' => $cashier->isActiveAccount(),
                                    'update_url' => route('store.backend.cashiers.update', [$store->slug, $cashier->id]),
                                ];
                            @endphp
                            <div class="d-inline-flex gap-1">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Edit Kasir"
                                        data-cashier='@json($cashierPayload)'
                                        onclick="openEditCashier(JSON.parse(this.dataset.cashier))">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                <form action="{{ route('store.backend.cashiers.toggle', [$store->slug, $cashier->id]) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm {{ $cashier->isActiveAccount() ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                            title="{{ $cashier->isActiveAccount() ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}">
                                        <i class="fa-solid {{ $cashier->isActiveAccount() ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                    </button>
                                </form>

                                <form action="{{ route('store.backend.cashiers.destroy', [$store->slug, $cashier->id]) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Hapus akun kasir {{ addslashes($cashier->name) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Kasir">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-cash-register fa-2x mb-2 d-block opacity-25"></i>
                            <div class="fw-semibold">Belum ada akun kasir untuk {{ $store->name }}</div>
                            <div class="small mb-3">Tambahkan akun kasir agar staf outlet bisa langsung melayani pesanan walk-in.</div>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddCashier">
                                <i class="fa-solid fa-user-plus me-1"></i> Tambah Kasir Pertama
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Tambah Akun Kasir --}}
<div class="modal fade" id="modalAddCashier" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('store.backend.cashiers.store', $store->slug) }}" method="POST" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2"></i>Tambah Akun Kasir</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Kasir <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Rina Kasir Pagi" value="{{ old('name') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Username Login <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control font-mono" placeholder="Contoh: kasir_rina" value="{{ old('username') }}" required>
                    <div class="form-text">Huruf, angka, tanda hubung (-) atau garis bawah (_). Dipakai untuk login Web &amp; APK.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Email <span class="text-muted fw-normal">(Opsional)</span></label>
                    <input type="email" name="email" class="form-control" placeholder="Kosongkan untuk otomatis" value="{{ old('email') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" minlength="6" required>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="addCashierActive" checked>
                    <label class="form-check-label small fw-semibold" for="addCashierActive">Akun Aktif (Bisa langsung login)</label>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-semibold">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Kasir
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit Akun Kasir --}}
<div class="modal fade" id="modalEditCashier" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formEditCashier" method="POST" class="modal-content border-0 shadow">
            @csrf
            @method('PUT')
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Edit Akun Kasir</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nama Kasir <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="editCashierName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Username Login <span class="text-danger">*</span></label>
                    <input type="text" name="username" id="editCashierUsername" class="form-control font-mono" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Email <span class="text-muted fw-normal">(Opsional)</span></label>
                    <input type="email" name="email" id="editCashierEmail" class="form-control" placeholder="Biarkan kosong jika tidak diubah">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Password Baru <span class="text-muted fw-normal">(Opsional)</span></label>
                    <input type="password" name="password" id="editCashierPassword" class="form-control" placeholder="Isi hanya jika ingin reset password" minlength="6">
                </div>
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editCashierActive">
                    <label class="form-check-label small fw-semibold" for="editCashierActive">Akun Aktif</label>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-dark fw-semibold">
                    <i class="fa-solid fa-check me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function copyCashierUrl() {
        const text = document.getElementById('cashierLoginUrl')?.textContent?.trim() || '';
        if (!text) return;
        navigator.clipboard?.writeText(text).then(() => {
            alert('URL Login Kasir berhasil disalin:\n' + text);
        }).catch(() => {
            prompt('Salin URL Login Kasir:', text);
        });
    }

    function openEditCashier(data) {
        const form = document.getElementById('formEditCashier');
        form.action = data.update_url;
        document.getElementById('editCashierName').value = data.name || '';
        document.getElementById('editCashierUsername').value = data.username || '';
        document.getElementById('editCashierEmail').value = data.email || '';
        document.getElementById('editCashierPassword').value = '';
        document.getElementById('editCashierActive').checked = !!data.is_active;

        const modal = new bootstrap.Modal(document.getElementById('modalEditCashier'));
        modal.show();
    }
</script>
@endpush
