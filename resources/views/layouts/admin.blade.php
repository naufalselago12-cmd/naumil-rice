<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico?v=2">
    <title>@yield('title', 'Admin Dashboard') - Naumil Rice</title>

    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --sidebar-width: 260px;
            --transition-speed: 0.25s;
        }

        /* Base Reset */
        body {
            font-family: var(--font-family);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color var(--transition-speed), color var(--transition-speed);
        }

        /* ================= THEME 1: LITE (LIGHT) ================= */
        body.theme-light {
            background-color: #f4f6f9;
            color: #2c3e50;
            --topbar-bg: #ffffff;
            --topbar-border: #e2e8f0;
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --sidebar-text: #e2e8f0;
            --sidebar-active-bg: #e11d48;
            --card-bg: #ffffff;
            --card-border: #e2e8f0;
            --card-header-bg: #fafbfc;
            --table-hover: #f8fafc;
            --accent-color: #0284c7;
        }

        /* ================= THEME 2: DARK ================= */
        body.theme-dark {
            background-color: #0b1120;
            color: #e2e8f0;
            --topbar-bg: #0f172a;
            --topbar-border: #1e293b;
            --sidebar-bg: #090e1a;
            --sidebar-hover: #1e293b;
            --sidebar-text: #94a3b8;
            --sidebar-active-bg: #3b82f6;
            --card-bg: #0f172a;
            --card-border: #1e293b;
            --card-header-bg: #131c31;
            --table-hover: #17233d;
            --accent-color: #38bdf8;
        }

        body.theme-dark .table {
            --bs-table-bg: transparent;
            --bs-table-color: #e2e8f0;
            --bs-table-border-color: #1e293b;
        }
        body.theme-dark .form-control,
        body.theme-dark .form-select {
            background-color: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
        }
        body.theme-dark .form-control:focus,
        body.theme-dark .form-select:focus {
            background-color: #1e293b;
            color: #fff;
            border-color: #38bdf8;
        }

        /* ================= THEME 3: PRIMARY (BIRU / BLUE ADMINLTE) ================= */
        body.theme-primary {
            background-color: #f0f4f8;
            color: #1e293b;
            --topbar-bg: #0284c7;
            --topbar-text: #ffffff;
            --topbar-border: #0369a1;
            --sidebar-bg: #034c7c;
            --sidebar-hover: #075985;
            --sidebar-text: #bae6fd;
            --sidebar-active-bg: #38bdf8;
            --card-bg: #ffffff;
            --card-border: #e0e7ff;
            --card-header-bg: #f8fafc;
            --table-hover: #eff6ff;
            --accent-color: #0284c7;
        }

        body.theme-primary .main-navbar {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
            color: white !important;
        }
        body.theme-primary .main-navbar .nav-link,
        body.theme-primary .main-navbar .navbar-brand {
            color: rgba(255,255,255,0.95) !important;
        }

        /* Layout Structure */
        .wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Sidebar */
        .main-sidebar {
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1030;
            overflow-y: auto;
            transition: all var(--transition-speed);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .brand-link {
            display: flex;
            align-items: center;
            padding: 1.1rem 1.25rem;
            text-decoration: none;
            color: #ffffff;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            background: rgba(0,0,0,0.15);
        }
        .brand-link img {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 12px;
        }
        .brand-text {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        /* Sidebar Navigation */
        .sidebar-menu {
            list-style: none;
            padding: 0.75rem 0.6rem;
            margin: 0;
        }
        .sidebar-header {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 0.8rem 0.8rem 0.3rem;
            color: rgba(255,255,255,0.4);
            font-weight: 700;
        }
        .nav-item {
            margin-bottom: 2px;
        }
        .nav-link-side {
            display: flex;
            align-items: center;
            padding: 0.65rem 0.9rem;
            border-radius: 8px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.15s ease-in-out;
        }
        .nav-link-side i {
            width: 24px;
            font-size: 1.05rem;
            margin-right: 10px;
            opacity: 0.85;
        }
        .nav-link-side:hover {
            background-color: var(--sidebar-hover);
            color: #ffffff;
            transform: translateX(3px);
        }
        .nav-link-side.active {
            background-color: var(--sidebar-active-bg);
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .nav-link-side .badge {
            margin-left: auto;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* Content Area */
        .content-wrapper {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: margin-left var(--transition-speed);
        }

        /* Main Topbar */
        .main-navbar {
            background-color: var(--topbar-bg);
            border-bottom: 1px solid var(--topbar-border);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }

        /* Theme Buttons */
        .theme-btn {
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
        }
        .theme-btn.active {
            border-color: currentColor;
            box-shadow: 0 0 0 2px rgba(56,189,248,0.3);
        }

        /* Content Body */
        .content-body {
            padding: 1.75rem 1.5rem;
            flex: 1;
        }

        /* Cards */
        .card-custom {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }
        .card-custom:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .card-custom-header {
            background-color: var(--card-header-bg);
            border-bottom: 1px solid var(--card-border);
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-custom-title {
            font-size: 1.05rem;
            font-weight: 700;
            margin: 0;
        }

        /* Metric Box (AdminLTE style) */
        .small-box {
            border-radius: 12px;
            position: relative;
            display: block;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            color: #fff;
            overflow: hidden;
            padding: 1.25rem 1.25rem;
            transition: transform 0.2s;
        }
        .small-box:hover {
            transform: translateY(-3px);
        }
        .small-box .inner h3 {
            font-size: 2.2rem;
            font-weight: 800;
            margin: 0 0 6px 0;
            white-space: nowrap;
        }
        .small-box .inner p {
            font-size: 0.9rem;
            font-weight: 500;
            margin: 0;
            opacity: 0.9;
        }
        .small-box .icon {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 3rem;
            opacity: 0.22;
        }

        /* Footer */
        .main-footer {
            background-color: var(--topbar-bg);
            border-top: 1px solid var(--topbar-border);
            padding: 0.9rem 1.5rem;
            font-size: 0.85rem;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        @media (max-width: 992px) {
            .main-sidebar {
                transform: translateX(-100%);
            }
            .sidebar-open .main-sidebar {
                transform: translateX(0);
            }
            .content-wrapper {
                margin-left: 0;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="theme-light" id="adminBody">

<div class="wrapper">
    <!-- Main Sidebar -->
    <aside class="main-sidebar">
        <a href="{{ route('admin.dashboard') }}" class="brand-link">
            <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=100&q=80" alt="Logo">
            <div>
                <div class="brand-text">NAUMIL RICE</div>
                <small style="font-size: 0.68rem; color: #38bdf8; font-weight: 600;">E-COMMERCE FOOD & PO</small>
            </div>
        </a>

        <ul class="sidebar-menu">
            <li class="sidebar-header">MAIN NAVIGATION</li>
            
            <li class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link-side {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="sidebar-header">CABANG & MENU</li>

            <li class="nav-item">
                <a href="{{ route('admin.stores.index') }}" class="nav-link-side {{ request()->routeIs('admin.stores.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-store"></i>
                    <span>Cabang / Toko</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('admin.categories.index') }}" class="nav-link-side {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Kategori Menu</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('admin.menus.index') }}" class="nav-link-side {{ request()->routeIs('admin.menus.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-bowl-food"></i>
                    <span>Menu Makanan & Topping</span>
                </a>
            </li>

            <li class="sidebar-header">PRE-ORDER & QR CAMPAIGN</li>

            <li class="nav-item">
                <a href="{{ route('admin.campaigns.index') }}" class="nav-link-side {{ request()->routeIs('admin.campaigns.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span>Campaign Open PO & QR</span>
                </a>
            </li>

            <li class="sidebar-header">TRANSAKSI & DAPUR</li>

            <li class="nav-item">
                <a href="{{ route('admin.orders.index') }}" class="nav-link-side {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Pesanan & Monitoring</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('admin.kds') }}" class="nav-link-side {{ request()->routeIs('admin.kds') ? 'active' : '' }}">
                    <i class="fa-solid fa-desktop"></i>
                    <span>KDS Kitchen Display</span>
                    <span class="badge bg-danger pulse">LIVE</span>
                </a>
            </li>

            <li class="sidebar-header">LAPORAN & TEKNIS</li>

            <li class="nav-item">
                <a href="{{ route('admin.reports') }}" class="nav-link-side {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Laporan Order Status</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('admin.apiDocs') }}" class="nav-link-side {{ request()->routeIs('admin.apiDocs') ? 'active' : '' }}">
                    <i class="fa-solid fa-code"></i>
                    <span>Dokumentasi API</span>
                </a>
            <li class="sidebar-header">AKUN ADMINISTRATOR</li>

            <li class="nav-item">
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="nav-link-side w-100 text-start border-0 bg-transparent text-danger">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Keluar (Logout)</span>
                    </button>
                </form>
            </li>
        </ul>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Top Navbar -->
        <nav class="main-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggleBtn">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="fw-bold d-none d-md-block">
                    <i class="fa-solid fa-utensils text-danger me-2"></i>
                    Food E-Commerce Management System
                </div>
            </div>

            <!-- Navbar Right Controls: Theme Switcher & Links -->
            <div class="d-flex align-items-center gap-2">
                <!-- Theme Switcher -->
                <div class="btn-group bg-light p-1 rounded-3 border" role="group">
                    <button type="button" class="btn btn-sm theme-btn" onclick="setTheme('theme-light')" id="btn-theme-light" title="Light Theme">
                        ☀️ Lite
                    </button>
                    <button type="button" class="btn btn-sm theme-btn" onclick="setTheme('theme-dark')" id="btn-theme-dark" title="Dark Theme">
                        🌙 Dark
                    </button>
                    <button type="button" class="btn btn-sm theme-btn" onclick="setTheme('theme-primary')" id="btn-theme-primary" title="Primary Blue Theme">
                        🔵 Primary (Biru)
                    </button>
                </div>

                <!-- Fullscreen KDS Quick Link -->
                <a href="{{ route('kds.fullscreen') }}" target="_blank" class="btn btn-sm btn-danger d-flex align-items-center gap-1 shadow-sm">
                    <i class="fa-solid fa-tv"></i>
                    <span class="d-none d-sm-inline">KDS</span>
                </a>

                <!-- Frontend Preview Link (Single Domain) -->
                <a href="/" target="_blank" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1">
                    <i class="fa-solid fa-mobile-screen"></i>
                    <span class="d-none d-sm-inline">Frontend</span>
                </a>

                <!-- User Profile & Logout -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-circle-user text-primary fs-6"></i>
                        <span class="d-none d-md-inline fw-semibold">{{ Auth::user()->name ?? 'naufal_admin' }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li class="px-3 py-1 text-muted small">
                            Login sebagai: <strong>{{ Auth::user()->username ?? 'naufal_admin' }}</strong>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('admin.logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-right-from-bracket"></i> Keluar (Logout)
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Body Content -->
        <main class="content-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <div class="fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-2"></i> Periksa kesalahan input berikut:</div>
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Main Footer -->
        <footer class="main-footer">
            <div>
                <strong>&copy; 2026 Naumil Rice.</strong> High Performance Food E-Commerce & PO System.
            </div>
            <div>
                <span>AdminLTE Multi-Theme Edition</span> | <span class="badge bg-success">Online</span>
            </div>
        </footer>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Theme Switcher Logic
    function setTheme(themeName) {
        const body = document.getElementById('adminBody');
        body.classList.remove('theme-light', 'theme-dark', 'theme-primary');
        body.classList.add(themeName);
        localStorage.setItem('naumil_admin_theme', themeName);

        // Update active buttons
        document.querySelectorAll('.theme-btn').forEach(btn => btn.classList.remove('active', 'btn-primary', 'btn-dark', 'btn-secondary'));
        if (themeName === 'theme-light') {
            document.getElementById('btn-theme-light').classList.add('active', 'btn-secondary');
        } else if (themeName === 'theme-dark') {
            document.getElementById('btn-theme-dark').classList.add('active', 'btn-dark');
        } else if (themeName === 'theme-primary') {
            document.getElementById('btn-theme-primary').classList.add('active', 'btn-primary');
        }
    }

    // Load saved theme on initial render
    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('naumil_admin_theme') || 'theme-light';
        setTheme(savedTheme);

        // Mobile Sidebar Toggle
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                document.body.classList.toggle('sidebar-open');
            });
        }
    });
</script>

@stack('scripts')
</body>
</html>
