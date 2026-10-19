<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=3">
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico?v=3">
    <link rel="icon" type="image/png" sizes="192x192" href="/favicon-192x192.png?v=3">
    <title>Login Administrator - Naumil Rice Backend</title>

    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --font-family: 'Plus Jakarta Sans', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        body {
            font-family: var(--font-family);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            transition: background 0.3s, color 0.3s;
        }

        /* Themes */
        body.theme-light {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: #1e293b;
            --card-bg: #ffffff;
            --card-border: #cbd5e1;
        }

        body.theme-dark {
            background: linear-gradient(135deg, #0b1120 0%, #020617 100%);
            color: #f1f5f9;
            --card-bg: #0f172a;
            --card-border: #1e293b;
        }

        body.theme-primary {
            background: linear-gradient(135deg, #0369a1 0%, #0c4a6e 100%);
            color: #1e293b;
            --card-bg: #ffffff;
            --card-border: #bae6fd;
        }

        .login-card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
        }

        .brand-header {
            padding: 2rem 2rem 1.25rem;
            text-align: center;
            border-bottom: 1px solid var(--card-border);
        }

        .brand-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
            color: white;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.3);
        }

        .form-container {
            padding: 2rem;
        }

        .theme-selector {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-bottom: 1.5rem;
        }

        .theme-btn {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: white;
            cursor: pointer;
        }
        .theme-btn.active {
            background: #0284c7;
            color: white;
            border-color: #0284c7;
        }

        .credentials-hint {
            background: #eff6ff;
            border: 1px dashed #93c5fd;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.82rem;
            color: #1e40af;
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body class="theme-light" id="loginBody">

<div class="login-card">
    <div class="brand-header">
        <div class="brand-icon">
            <i class="fa-solid fa-bowl-rice"></i>
        </div>
        <h3 class="fw-bold mb-1">NAUMIL RICE</h3>
        <p class="text-muted small mb-0">Portal Masuk Backend Administrator</p>
    </div>

    <div class="form-container">
        <!-- Theme Switcher -->
        <div class="theme-selector">
            <button type="button" class="theme-btn active" onclick="setTheme('theme-light')" id="btn-lite">☀️ Lite</button>
            <button type="button" class="theme-btn" onclick="setTheme('theme-dark')" id="btn-dark">🌙 Dark</button>
            <button type="button" class="theme-btn" onclick="setTheme('theme-primary')" id="btn-primary">🔵 Primary</button>
        </div>

        @if(session('success'))
            <div class="alert alert-success py-2 small mb-3">
                <i class="fa-solid fa-circle-check me-1"></i> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger py-2 small mb-3">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $errors->first() }}
            </div>
        @endif

        <!-- Quick Credentials Info Box -->
        <div class="credentials-hint">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <strong class="d-block"><i class="fa-solid fa-key me-1"></i> Akun Administrator:</strong>
                <button type="button" class="btn btn-xs btn-link p-0 text-primary text-decoration-none fw-bold small" onclick="fillAdminCredentials()">
                    Auto-fill Akun
                </button>
            </div>
            <div>Username: <strong class="font-monospace">naufal_admin</strong></div>
            <div>Password: <strong class="font-monospace">naufal2020</strong></div>
        </div>

        <form action="{{ route('admin.login.submit') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-bold small">Username / Email Administrator</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" id="usernameInput" 
                           class="form-control @error('username') is-invalid @enderror" 
                           placeholder="naufal_admin" value="{{ old('username', 'naufal_admin') }}" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small">Kata Sandi (Password)</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" id="passwordInput" 
                           class="form-control" placeholder="••••••••" value="naufal2020" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                        <i class="fa-solid fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="rememberMe" checked>
                    <label class="form-check-label small" for="rememberMe">Ingat saya</label>
                </div>
                <a href="/" class="small text-decoration-none text-muted">
                    <i class="fa-solid fa-arrow-left me-1"></i> Ke Frontend
                </a>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                <i class="fa-solid fa-right-to-bracket me-1"></i> Masuk ke Backend
            </button>
        </form>
    </div>
</div>

<script>
    function setTheme(theme) {
        const body = document.getElementById('loginBody');
        body.className = theme;
        localStorage.setItem('naumil_admin_theme', theme);

        document.querySelectorAll('.theme-btn').forEach(b => b.classList.remove('active'));
        if (theme === 'theme-light') document.getElementById('btn-lite').classList.add('active');
        if (theme === 'theme-dark') document.getElementById('btn-dark').classList.add('active');
        if (theme === 'theme-primary') document.getElementById('btn-primary').classList.add('active');
    }

    function togglePassword() {
        const input = document.getElementById('passwordInput');
        const icon = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fa-solid fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fa-solid fa-eye';
        }
    }

    function fillAdminCredentials() {
        document.getElementById('usernameInput').value = 'naufal_admin';
        document.getElementById('passwordInput').value = 'naufal2020';
    }

    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('naumil_admin_theme') || 'theme-light';
        setTheme(savedTheme);
    });
</script>
</body>
</html>
