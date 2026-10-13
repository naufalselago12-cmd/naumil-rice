<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Backend - {{ $store->name }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --font-family: 'Plus Jakarta Sans', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        body {
            font-family: var(--font-family);
            background-color: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            padding: 2.25rem 2rem 1.75rem 2rem;
            text-align: center;
            color: #fff;
        }

        .store-logo-img {
            width: 64px;
            height: 64px;
            border-radius: 14px;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.4);
            margin-bottom: 0.85rem;
            background-color: #fff;
        }

        .btn-store-submit {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #fff;
            border: none;
            padding: 0.8rem;
            border-radius: 10px;
            font-weight: 700;
            transition: all 0.2s;
        }

        .btn-store-submit:hover {
            background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <img src="{{ $store->logo ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=100&q=80' }}" 
             alt="{{ $store->name }}" class="store-logo-img shadow-sm">
        <span class="badge bg-danger font-mono d-inline-block mb-1">{{ $store->code }}</span>
        <h3 class="fw-bold fs-5 mb-1">{{ $store->name }}</h3>
        <small class="opacity-75">Panel Dapur & Administrator Cabang</small>
    </div>

    <div class="p-4 p-md-4">
        @if($errors->any())
            <div class="alert alert-danger border-0 p-3 mb-3 rounded-3 small">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $errors->first() }}
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success border-0 p-3 mb-3 rounded-3 small">
                <i class="fa-solid fa-circle-check me-1"></i> {{ session('success') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning border-0 p-3 mb-3 rounded-3 small">
                <i class="fa-solid fa-circle-info me-1"></i> {{ session('warning') }}
            </div>
        @endif

        <form action="{{ route('store.backend.login.submit', $store->slug) }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-bold mb-1">Username Admin Toko</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" class="form-control" 
                           placeholder="Username pengelola toko" value="{{ old('username') }}" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold mb-1">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" 
                           placeholder="Password akun" required>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="rememberMe" checked>
                    <label class="form-check-label small text-muted" for="rememberMe">Ingat saya</label>
                </div>
                <a href="{{ url('/' . $store->slug) }}" class="small text-decoration-none">
                    <i class="fa-solid fa-arrow-left me-1"></i> Halaman Toko
                </a>
            </div>

            <button type="submit" class="btn btn-store-submit w-100">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Masuk Backend Cabang
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top">
            <small class="text-muted d-block">Super Admin juga dapat login melalui form ini.</small>
            <a href="{{ route('superadmin.login') }}" class="small text-danger fw-semibold text-decoration-none">
                <i class="fa-solid fa-crown me-1"></i> Masuk ke Super Admin Portal
            </a>
        </div>
    </div>
</div>

</body>
</html>
