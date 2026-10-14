<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin Portal') - Naumil Rice Master Control</title>

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
            --sidebar-width: 270px;
            --super-accent: #f43f5e;
            --super-accent-hover: #e11d48;
            --super-dark: #0f172a;
            --super-dark-card: #1e293b;
            --super-border: #334155;
        }

        body {
            font-family: var(--font-family);
            background-color: #0b1120;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Sidebar */
        .super-sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #090e1a 0%, #0f172a 100%);
            border-right: 1px solid var(--super-border);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1030;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            transition: transform 0.25s ease-in-out;
        }

        .super-brand {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--super-border);
            text-decoration: none;
            color: #fff;
        }

        .super-brand-badge {
            background: linear-gradient(135deg, #f43f5e 0%, #be123c 100%);
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #fff;
            box-shadow: 0 4px 14px rgba(244, 63, 94, 0.4);
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
            color: #64748b;
            padding: 0.75rem 0.75rem 0.35rem 0.75rem;
            font-weight: 700;
        }

        .nav-item {
            margin-bottom: 4px;
        }

        .nav-link-side {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.65rem 0.85rem;
            color: #94a3b8;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-link-side:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.05);
        }

        .nav-link-side.active {
            color: #fff;
            background: linear-gradient(90deg, #f43f5e 0%, #e11d48 100%);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(244, 63, 94, 0.3);
        }

        /* Content Area */
        .super-wrapper {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: margin-left 0.25s ease-in-out;
        }

        /* Navbar */
        .super-navbar {
            background-color: #0f172a;
            border-bottom: 1px solid var(--super-border);
            padding: 0.85rem 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .super-content {
            padding: 1.75rem;
            flex: 1;
        }

        /* Cards */
        .card-dark {
            background-color: var(--super-dark-card);
            border: 1px solid var(--super-border);
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-dark-header {
            background-color: rgba(15, 23, 42, 0.6);
            border-bottom: 1px solid var(--super-border);
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-dark-title {
            font-size: 1.05rem;
            font-weight: 700;
            margin: 0;
            color: #fff;
        }

        /* Tables */
        .table-dark-custom {
            --bs-table-bg: transparent;
            --bs-table-color: #e2e8f0;
            --bs-table-border-color: var(--super-border);
            margin-bottom: 0;
        }

        .table-dark-custom thead th {
            background-color: rgba(15, 23, 42, 0.7);
            color: #94a3b8;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.85rem 1rem;
            font-weight: 700;
            border-bottom: 1px solid var(--super-border);
        }

        .table-dark-custom tbody td {
            padding: 0.85rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(51, 65, 85, 0.6);
            font-size: 0.88rem;
        }

        .table-dark-custom tbody tr:hover td {
            background-color: rgba(255, 255, 255, 0.03);
        }

        /* Metric Box */
        .metric-card {
            background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid var(--super-border);
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s;
        }

        .metric-card:hover {
            transform: translateY(-3px);
            border-color: #475569;
        }

        .metric-card .metric-val {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 4px;
            color: #fff;
            font-family: var(--font-mono);
        }

        .metric-card .metric-label {
            font-size: 0.85rem;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .metric-card .metric-icon {
            position: absolute;
            right: 1.25rem;
            top: 1.25rem;
            font-size: 2.5rem;
            opacity: 0.15;
            color: #fff;
        }

        /* Forms */
        .form-control-dark, .form-select-dark {
            background-color: #0f172a;
            border: 1px solid var(--super-border);
            color: #f1f5f9;
            border-radius: 8px;
            padding: 0.6rem 0.85rem;
        }

        .form-control-dark:focus, .form-select-dark:focus {
            background-color: #0f172a;
            border-color: var(--super-accent);
            color: #fff;
            box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.25);
        }

        .form-label-dark {
            font-size: 0.85rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 0.35rem;
        }

        .btn-super {
            background: linear-gradient(135deg, #f43f5e 0%, #be123c 100%);
            color: #fff;
            border: none;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.55rem 1.25rem;
            box-shadow: 0 4px 12px rgba(244, 63, 94, 0.3);
            transition: all 0.2s;
        }

        .btn-super:hover {
            background: linear-gradient(135deg, #e11d48 0%, #9f1239 100%);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(244, 63, 94, 0.4);
        }

        @media (max-width: 992px) {
            .super-sidebar {
                transform: translateX(-100%);
            }
            .sidebar-open .super-sidebar {
                transform: translateX(0);
            }
            .super-wrapper {
                margin-left: 0;
            }
        }
    </style>
    @stack('styles')
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <aside class="super-sidebar" id="superSidebar">
        <a href="{{ route('superadmin.dashboard') }}" class="super-brand">
            <div class="super-brand-badge">
                <i class="fa-solid fa-crown"></i>
            </div>
            <div>
                <div class="fw-bold" style="letter-spacing: 0.05em; font-size: 1rem;">SUPER ADMIN</div>
                <small style="color: var(--super-accent); font-weight: 700; font-size: 0.7rem;">NAUMIL RICE GLOBAL</small>
            </div>
        </a>

        <ul class="sidebar-menu">
            <li class="sidebar-header">MASTER CONTROL</li>

            <li class="nav-item">
                <a href="{{ route('superadmin.dashboard') }}" class="nav-link-side {{ request()->routeIs('superadmin.dashboard*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Dashboard Global</span>
                </a>
            </li>

            <li class="sidebar-header">MULTI-STORE MANAGEMENT</li>

            <li class="nav-item">
                <a href="{{ route('superadmin.stores.index') }}" class="nav-link-side {{ request()->routeIs('superadmin.stores.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-store"></i>
                    <span>Kelola Toko & URL Cabang</span>
                </a>
            </li>

            <li class="sidebar-header">PENGGUNA & PENUGASAN</li>

            <li class="nav-item">
                <a href="{{ route('superadmin.users.index') }}" class="nav-link-side {{ request()->routeIs('superadmin.users.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users-gear"></i>
                    <span>Kelola User Per Toko</span>
                </a>
            </li>

            <li class="sidebar-header">PORTAL & SHORTCUTS</li>

            <li class="nav-item">
                <a href="{{ url('/') }}" target="_blank" class="nav-link-side">
                    <i class="fa-solid fa-globe"></i>
                    <span>Lihat Halaman Utama Web</span>
                </a>
            </li>

            <li class="sidebar-header">AKUN SUPER ADMIN</li>

            <li class="nav-item">
                <form action="{{ route('superadmin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="nav-link-side w-100 text-start border-0 bg-transparent text-danger">
                        <i class="fa-solid fa-power-off"></i>
                        <span>Keluar (Logout)</span>
                    </button>
                </form>
            </li>
        </ul>
    </aside>

    <!-- Main Wrapper -->
    <div class="super-wrapper">
        <!-- Navbar -->
        <header class="super-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-lg-none text-white border-secondary" id="sidebarToggleBtn">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="d-none d-md-block">
                    <span class="badge" style="background-color: rgba(244, 63, 94, 0.2); color: #f43f5e; border: 1px solid rgba(244, 63, 94, 0.4);">
                        <i class="fa-solid fa-shield-halved me-1"></i> SUPER ADMINISTRATOR LEVEL
                    </span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                @php
                    $currentSuperUser = auth('superadmin')->user() ?? auth()->user();
                @endphp
                <!-- User Profile Badge -->
                <div class="d-flex align-items-center gap-2">
                    <div class="text-end d-none d-sm-block">
                        <div class="fw-bold fs-7 text-white">{{ $currentSuperUser->name ?? 'Super Administrator' }}</div>
                        <small class="text-muted font-mono" style="font-size: 0.75rem;">@<span>{{ $currentSuperUser->username ?? 'naufal_su' }}</span></small>
                    </div>
                    <div style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #f43f5e, #be123c); display: flex; align-items: center; justify-content: center; font-weight: bold; color: white;">
                        {{ strtoupper(substr($currentSuperUser->name ?? 'N', 0, 1)) }}
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <main class="super-content">
            <!-- Flash Notifications -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="background-color: rgba(16, 185, 129, 0.2); color: #34d399; border-left: 4px solid #10b981 !important;">
                    <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="background-color: rgba(244, 63, 94, 0.2); color: #fb7185; border-left: 4px solid #f43f5e !important;">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="background-color: rgba(245, 158, 11, 0.2); color: #fbbf24; border-left: 4px solid #f59e0b !important;">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('warning') }}
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggleBtn')?.addEventListener('click', function() {
        document.body.classList.toggle('sidebar-open');
    });
</script>
@stack('scripts')
</body>
</html>
