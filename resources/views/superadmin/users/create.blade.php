@extends('superadmin.layouts.app')

@section('title', 'Tambah User Toko Baru')

@section('content')
<div class="mb-4">
    <a href="{{ route('superadmin.users.index') }}" class="text-muted text-decoration-none small">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Daftar User
    </a>
    <h2 class="fw-bold mt-2 text-white">Tambah User Admin Per Toko</h2>
    <p class="text-muted mb-0">Tugaskan user ke cabang tertentu agar dapat mengelola backend toko tersebut.</p>
</div>

<div class="card-dark max-w-700">
    <div class="card-dark-header">
        <h5 class="card-dark-title"><i class="fa-solid fa-user-plus text-danger me-2"></i> Formulir User Baru & Penugasan Toko</h5>
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

        <form action="{{ route('superadmin.users.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label-dark">Nama Lengkap User <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control form-control-dark" 
                       placeholder="Contoh: Budi Santoso - Manager Sudirman" value="{{ old('name') }}" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label-dark">Username Login <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary">@</span>
                        <input type="text" name="username" class="form-control form-control-dark font-mono" 
                               placeholder="admin_sudirman" value="{{ old('username') }}" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label-dark">Alamat Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control form-control-dark" 
                           placeholder="budi@naumilrice.com" value="{{ old('email') }}" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label-dark">Password Login <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control form-control-dark font-mono" 
                       placeholder="Minimal 6 karakter" required>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-5">
                    <label class="form-label-dark">Peran / Role Pengguna <span class="text-danger">*</span></label>
                    <select name="role" id="roleSelect" class="form-select form-select-dark" required onchange="toggleStoreSelect(this.value)">
                        <option value="store_admin" {{ old('role', 'store_admin') === 'store_admin' ? 'selected' : '' }}>
                            Store Admin (Khusus 1 Cabang)
                        </option>
                        <option value="superadmin" {{ old('role') === 'superadmin' ? 'selected' : '' }}>
                            Super Admin (Seluruh Cabang)
                        </option>
                    </select>
                </div>

                <div class="col-md-7" id="storeSelectGroup">
                    <label class="form-label-dark">Tugaskan Ke Cabang / Toko <span class="text-danger">*</span></label>
                    <select name="store_id" id="storeSelect" class="form-select form-select-dark">
                        <option value="">-- Pilih Cabang Yang Dikelola --</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ old('store_id') == $st->id ? 'selected' : '' }}>
                                {{ $st->name }} ({{ $st->code }}) [URL: /{{ $st->slug }}]
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">User hanya dapat mengakses backend <code>base_url/(nama_toko)/backend</code> untuk toko ini.</small>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('superadmin.users.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-super px-4">
                    <i class="fa-solid fa-save me-1"></i> Buat User Baru
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function toggleStoreSelect(role) {
    const group = document.getElementById('storeSelectGroup');
    const storeSelect = document.getElementById('storeSelect');
    if (role === 'superadmin') {
        group.style.opacity = '0.4';
        storeSelect.disabled = true;
    } else {
        group.style.opacity = '1';
        storeSelect.disabled = false;
    }
}
toggleStoreSelect(document.getElementById('roleSelect').value);
</script>
@endpush
@endsection
