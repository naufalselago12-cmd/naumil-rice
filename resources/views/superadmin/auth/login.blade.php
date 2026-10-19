<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=3">
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico?v=3">
    <link rel="icon" type="image/png" sizes="192x192" href="/favicon-192x192.png?v=3">
    <title>Super Admin Login - Naumil Rice Master Control</title>

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
            background-color: #0b1120;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: radial-gradient(rgba(244, 63, 94, 0.12) 1px, transparent 1px),
                              radial-gradient(rgba(59, 130, 246, 0.12) 1px, transparent 1px);
            background-size: 40px 40px;
            background-position: 0 0, 20px 20px;
            padding: 1.5rem;
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 0 0 40px rgba(244, 63, 94, 0.1);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
            padding: 2.25rem 2rem 1.75rem 2rem;
            text-align: center;
            border-bottom: 1px solid #1f2937;
        }

        .super-badge-icon {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            background: linear-gradient(135deg, #f43f5e 0%, #be123c 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: #fff;
            margin-bottom: 1rem;
            box-shadow: 0 8px 24px rgba(244, 63, 94, 0.35);
        }

        .form-control-dark {
            background-color: #0d131f;
            border: 1px solid #2d3748;
            color: #fff;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            transition: all 0.2s;
        }

        .form-control-dark:focus {
            background-color: #0d131f;
            border-color: #f43f5e;
            color: #fff;
            box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.25);
        }

        .btn-super-submit {
            background: linear-gradient(135deg, #f43f5e 0%, #be123c 100%);
            color: #fff;
            border: none;
            padding: 0.85rem;
            border-radius: 10px;
            font-weight: 700;
            letter-spacing: 0.03em;
            transition: all 0.2s;
            box-shadow: 0 6px 18px rgba(244, 63, 94, 0.35);
        }

        .btn-super-submit:hover {
            background: linear-gradient(135deg, #e11d48 0%, #9f1239 100%);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(244, 63, 94, 0.45);
        }

        .credentials-pill {
            background-color: rgba(244, 63, 94, 0.1);
            border: 1px dashed rgba(244, 63, 94, 0.35);
            border-radius: 10px;
            padding: 0.85rem;
            font-size: 0.82rem;
            color: #fda4af;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="super-badge-icon">
            <i class="fa-solid fa-crown"></i>
        </div>
        <h2 class="fw-bold mb-1 fs-4 text-white">Super Admin Portal</h2>
        <p class="small mb-0 text-white">Master Control Cabang & Manajemen User Naumil Rice</p>
    </div>

    <div class="p-4 p-md-5">
        @if($errors->any())
            <div class="alert alert-danger border-0 p-3 mb-4 rounded-3" style="background-color: rgba(244, 63, 94, 0.2); color: #fecdd3;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>{{ $errors->first() }}</div>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success border-0 p-3 mb-4 rounded-3" style="background-color: rgba(16, 185, 129, 0.2); color: #a7f3d0;">
                <i class="fa-solid fa-circle-check me-1"></i> {{ session('success') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning border-0 p-3 mb-4 rounded-3" style="background-color: rgba(245, 158, 11, 0.2); color: #fde68a;">
                <i class="fa-solid fa-circle-info me-1"></i> {{ session('warning') }}
            </div>
        @endif

        <!-- Hint credentials reminder for user 
        <div class="credentials-pill mb-4 cursor-pointer" onclick="fillSuperCredentials()" title="Klik untuk mengisi otomatis">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <strong><i class="fa-solid fa-key me-1"></i> Default Kredensial Super Admin:</strong>
                <span class="badge bg-danger" style="font-size: 0.68rem;">Auto-Fill</span>
            </div>
            <div>Username: <code class="text-white fw-bold">naufal_su</code></div>
            <div>Password: <code class="text-white fw-bold">naufal_super2020</code></div>
        </div>
        -->
        <form action="{{ route('superadmin.login.submit') }}" method="POST">
            @csrf
            
            <div class="mb-3">
                <label class="form-label small fw-semibold text-light mb-1">Username Super Admin</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-user-shield"></i></span>
                    <input type="text" name="username" id="usernameInput" class="form-control form-control-dark" 
                           placeholder="Contoh: naufal_su" value="{{ old('username', 'naufal_su') }}" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold text-light mb-1">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" id="passwordInput" class="form-control form-control-dark" 
                           placeholder="Masukkan password" value="" required>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input bg-dark border-secondary" type="checkbox" name="remember" id="rememberMe" checked>
                    <label class="form-check-label small ttext-white" for="rememberMe">Ingat saya</label>
                </div>
                <a href="{{ url('/') }}" class="small text-muted text-decoration-none hover-white">
                    <i class="fa-solid fa-arrow-left me-1"></i> Ke Beranda
                </a>
            </div>

            <button type="submit" class="btn btn-super-submit w-100">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Masuk Super Admin
            </button>
        </form>
    </div>
</div>

<script>
function fillSuperCredentials() {
    document.getElementById('usernameInput').value = 'naufal_su';
    document.getElementById('passwordInput').value = 'naufal_super2020';
}
</script>

</body>
</html>
