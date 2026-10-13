@extends('superadmin.layouts.app')

@section('title', 'Edit User ' . $user->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('superadmin.users.index') }}" class="text-muted text-decoration-none small">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Daftar User
    </a>
    <h2 class="fw-bold mt-2 text-white">Edit User: {{ $user->name }}</h2>
    <p class="text-muted mb-0">Ubah penugasan cabang toko, username, atau reset password user.</p>
</div>

<div class="card-dark max-w-700">
    <div class="card-dark-header">
        <h5 class="card-dark-title"><i class="fa-solid fa-user-pen text-danger me-2"></i> Perbarui Data & Penugasan Toko</h5>
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

        <form action="{{ route('superadmin.users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label-dark">Nama Lengkap User <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control form-control-dark" 
                       value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label-dark">Username Login <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary">@</span>
                        <input type="text" name="username" class="form-control form-control-dark font-mono" 
                               value="{{ old('username', $user->username) }}" required 
                               {{ $user->username === 'naufal_su' ? 'readonly' : '' }}>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label-dark">Alamat Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control form-control-dark" 
                           value="{{ old('email', $user->email) }}" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label-dark">Ganti Password (Kosongkan jika tidak ingin mengubah)</label>
                <input type="password" name="password" class="form-control form-control-dark font-mono" 
                       placeholder="Masukkan password baru">
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-5">
                    <label class="form-label-dark">Peran / Role Pengguna <span class="text-danger">*</span></label>
                    <select name="role" id="roleSelect" class="form-select form-select-dark" required onchange="toggleStoreSelect(this.value)"
                            {{ $user->username === 'naufal_su' ? 'disabled' : '' }}>
                        <option value="store_admin" {{ old('role', $user->role) === 'store_admin' ? 'selected' : '' }}>
                            Store Admin (Khusus 1 Cabang)
                        </option>
                        <option value="superadmin" {{ old('role', $user->role) === 'superadmin' ? 'selected' : '' }}>
                            Super Admin (Seluruh Cabang)
                        </option>
                    </select>
                    @if($user->username === 'naufal_su')
                        <input type="hidden" name="role" value="superadmin">
                    @endif
                </div>

                <div class="col-md-7" id="storeSelectGroup">
                    <label class="form-label-dark">Tugaskan Ke Cabang / Toko <span class="text-danger">*</span></label>
                    <select name="store_id" id="storeSelect" class="form-select form-select-dark">
                        <option value="">-- Pilih Cabang Yang Dikelola --</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ old('store_id', $user->store_id) == $st->id ? 'selected' : '' }}>
                                {{ $st->name }} ({{ $st->code }}) [URL: /{{ $st->slug }}]
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('superadmin.users.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-super px-4">
                    <i class="fa-solid fa-save me-1"></i> Perbarui User
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
