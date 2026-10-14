<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico?v=2">
    <title>@yield('title', 'Panel Cabang') - {{ $currentStore->name ?? 'Backend Toko' }}</title>

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
            --store-primary: #0284c7;
            --store-dark: #0f172a;
            --store-dark-card: #1e293b;
            --store-border: #e2e8f0;
        }

        body {
            font-family: var(--font-family);
            background-color: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Sidebar */
        .store-sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1030;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            color: #fff;
            box-shadow: 2px 0 12px rgba(0,0,0,0.1);
        }

        .store-brand-header {
            padding: 1.25rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-decoration: none;
            color: #fff;
        }

        .store-brand-logo {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.2);
        }

        .sidebar-menu {
            list-style: none;
            padding: 1rem 0.75rem;
            margin: 0;
            flex: 1;
        }

        .sidebar-header {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            padding: 0.75rem 0.75rem 0.35rem 0.75rem;
            font-weight: 700;
        }

        .nav-link-side {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.65rem 0.85rem;
            color: #cbd5e1;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.2s;
            margin-bottom: 3px;
        }

        .nav-link-side:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.08);
        }

        .nav-link-side.active {
            color: #fff;
            background: linear-gradient(90deg, #0284c7 0%, #0369a1 100%);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35);
        }

        .store-wrapper {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .store-topbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.85rem 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }

        .store-content {
            padding: 1.75rem;
            flex: 1;
        }

        /* Metric Box */
        .metric-box-light {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            position: relative;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            transition: transform 0.2s;
        }

        .metric-box-light:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.07);
        }

        .metric-box-light .val {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 4px;
            color: #0f172a;
            font-family: var(--font-mono);
        }

        .metric-box-light .lbl {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }

        .metric-box-light .icon {
            position: absolute;
            right: 1.25rem;
            top: 1.25rem;
            font-size: 2.25rem;
            opacity: 0.15;
            color: #0284c7;
        }

        .card-custom {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-custom-header {
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-custom-title {
            font-size: 1.05rem;
            font-weight: 700;
            margin: 0;
            color: #0f172a;
        }

        @media (max-width: 992px) {
            .store-sidebar {
                transform: translateX(-100%);
            }
            .sidebar-open .store-sidebar {
                transform: translateX(0);
            }
            .store-wrapper {
                margin-left: 0;
            }
        }
    </style>
    @stack('styles')
</head>
<body>

<div class="d-flex">
    <!-- Store Sidebar -->
    <aside class="store-sidebar" id="storeSidebar">
        <a href="{{ route('store.backend.dashboard', $currentStore->slug) }}" class="store-brand-header">
            <img src="{{ $currentStore->logo ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=100&q=80' }}" 
                 alt="Store Logo" class="store-brand-logo">
            <div style="min-width: 0;">
                <div class="fw-bold text-truncate text-white" style="font-size: 0.95rem;">{{ $currentStore->name }}</div>
                <div class="d-flex align-items-center gap-1 mt-1">
                    <span class="badge bg-danger font-mono" style="font-size: 0.65rem;">{{ $currentStore->code }}</span>
                    <small class="text-info font-mono" style="font-size: 0.68rem;">/{{ $currentStore->slug }}</small>
                </div>
            </div>
        </a>

        <ul class="sidebar-menu">
            <li class="sidebar-header">PANEL CABANG</li>

            <li class="nav-item">
                <a href="{{ route('store.backend.dashboard', $currentStore->slug) }}" 
                   class="nav-link-side {{ request()->routeIs('store.backend.dashboard*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge"></i>
                    <span>Dashboard Dapur</span>
                </a>
            </li>

            <li class="sidebar-header">KATALOG MENU TOKO</li>

            <li class="nav-item">
                <a href="{{ route('store.backend.menus.index', $currentStore->slug) }}" 
                   class="nav-link-side {{ request()->routeIs('store.backend.menus*') ? 'active' : '' }}">
                    <i class="fa-solid fa-bowl-food"></i>
                    <span>Menu & Topping Cabang</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('store.backend.categories.index', $currentStore->slug) }}" 
                   class="nav-link-side {{ request()->routeIs('store.backend.categories*') ? 'active' : '' }}">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Kategori Menu</span>
                </a>
            </li>

            <li class="sidebar-header">PRE-ORDER & QR CAMPAIGN</li>

            <li class="nav-item">
                <a href="{{ route('store.backend.campaigns.index', $currentStore->slug) }}" 
                   class="nav-link-side {{ request()->routeIs('store.backend.campaigns*') ? 'active' : '' }}">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span>Campaign Open PO</span>
                </a>
            </li>

            <li class="sidebar-header">OPERASIONAL & TRANSAKSI</li>

            <li class="nav-item">
                <a href="{{ route('store.backend.orders.index', $currentStore->slug) }}" 
                   class="nav-link-side {{ request()->routeIs('store.backend.orders*') ? 'active' : '' }}">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Pesanan Cabang</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('store.backend.kds', $currentStore->slug) }}" 
                   class="nav-link-side {{ request()->routeIs('store.backend.kds*') ? 'active' : '' }}">
                    <i class="fa-solid fa-desktop"></i>
                    <span>Kitchen Display (KDS)</span>
                    <span class="badge bg-danger ms-auto">LIVE</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('store.backend.reports', $currentStore->slug) }}" 
                   class="nav-link-side {{ request()->routeIs('store.backend.reports*') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Laporan Penjualan</span>
                </a>
            </li>

            <li class="sidebar-header">PENGATURAN TOKO</li>

            <li class="nav-item">
                <a href="{{ route('store.backend.settings', $currentStore->slug) }}" 
                   class="nav-link-side {{ request()->routeIs('store.backend.settings*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gear"></i>
                    <span>Profil & QRIS Cabang</span>
                </a>
            </li>

            <li class="sidebar-header">AKUN & NAVIGASI</li>

            <li class="nav-item">
                <a href="{{ url('/' . $currentStore->slug) }}" target="_blank" class="nav-link-side text-info">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Buka Frontend Toko</span>
                </a>
            </li>

            @if((auth()->check() && auth()->user()->isSuperAdmin()) || \Illuminate\Support\Facades\Auth::guard('superadmin')->check())
                <li class="nav-item">
                    <a href="{{ route('superadmin.dashboard') }}" class="nav-link-side text-warning">
                        <i class="fa-solid fa-crown"></i>
                        <span>Super Admin Panel</span>
                    </a>
                </li>
            @endif

            <li class="nav-item">
                <form action="{{ route('store.backend.logout', $currentStore->slug) }}" method="POST">
                    @csrf
                    <button type="submit" class="nav-link-side w-100 text-start border-0 bg-transparent text-danger">
                        <i class="fa-solid fa-power-off"></i>
                        <span>Keluar (Logout)</span>
                    </button>
                </form>
            </li>
        </ul>
    </aside>

    <!-- Content Wrapper -->
    <div class="store-wrapper">
        <!-- Topbar -->
        <header class="store-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" id="storeSidebarToggle">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary font-mono">{{ $currentStore->code }}</span>
                    <strong class="fs-6 text-dark">{{ $currentStore->name }}</strong>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="{{ url('/' . $currentStore->slug) }}" target="_blank" class="btn btn-sm btn-outline-primary d-none d-sm-inline-flex align-items-center gap-1">
                    <i class="fa-solid fa-globe"></i> Frontend Pembeli
                </a>

                <div class="d-flex align-items-center gap-2">
                    <div class="text-end d-none d-sm-block">
                        <div class="fw-bold small">{{ auth()->user()->name ?? 'Store Admin' }}</div>
                        <small class="text-muted font-mono" style="font-size: 0.72rem;">@<span>{{ auth()->user()->username ?? 'admin' }}</span></small>
                    </div>
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #0284c7; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white;">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <main class="store-content">
            <!-- Flash Notifications -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <div class="fw-bold mb-1"><i class="fa-solid fa-circle-exclamation me-2"></i> Mohon periksa kesalahan input berikut:</div>
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('storeSidebarToggle')?.addEventListener('click', function() {
        document.body.classList.toggle('sidebar-open');
    });
</script>
@stack('scripts')
</body>
</html>
