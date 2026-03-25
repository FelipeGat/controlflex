<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ControleFlex') }} - @yield('title', 'Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --sidebar-bg: #1e1b4b;
            --sidebar-width: 255px;
        }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f1f5f9; }
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh;
            width: var(--sidebar-width); background: var(--sidebar-bg);
            overflow-y: auto; z-index: 50;
            display: flex; flex-direction: column;
        }
        .sidebar-link {
            display: flex; align-items: center; gap: 12px; padding: 10px 16px;
            color: #a5b4fc; text-decoration: none; border-radius: 8px; margin: 1px 8px;
            font-size: 14px; transition: all .2s;
        }
        .sidebar-link:hover, .sidebar-link.active {
            background: rgba(99,102,241,.3); color: #fff;
        }
        .sidebar-link i { width: 18px; text-align: center; }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .topbar {
            background: #fff; border-bottom: 1px solid #e2e8f0;
            padding: 12px 24px; display: flex; align-items: center;
            justify-content: space-between; position: sticky; top: 0; z-index: 40;
            box-shadow: 0 1px 2px rgba(0,0,0,.04);
        }
        .card {
            background: #fff; border-radius: 16px; padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,.07); border: 1px solid #e2e8f0;
        }
        .btn-primary {
            background: var(--primary); color: #fff; border: none; border-radius: 8px;
            padding: 9px 18px; font-size: 14px; cursor: pointer; display: inline-flex;
            align-items: center; gap: 8px; text-decoration: none; font-weight: 600;
            transition: background .2s;
        }
        .btn-primary:hover { background: var(--primary-dark); color: #fff; }
        .btn-sm { padding: 5px 10px; font-size: 12px; border-radius: 6px; }
        .btn-danger { background: #ef4444; color: #fff; border: none; border-radius: 8px; padding: 6px 12px; font-size: 13px; cursor: pointer; font-weight: 500; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: #f59e0b; color: #fff; border: none; border-radius: 8px; padding: 6px 12px; font-size: 13px; cursor: pointer; }
        .btn-secondary { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 16px; font-size: 14px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-secondary:hover { background: #f1f5f9; }
        .table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .table th { padding: 10px 16px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: .04em; border-bottom: 2px solid #f1f5f9; }
        .table td { padding: 12px 16px; border-bottom: 1px solid #f8fafc; color: #334155; vertical-align: middle; }
        .table tr:last-child td { border-bottom: none; }
        .table tr:hover td { background: #fafbfc; }
        .form-control {
            width: 100%; padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px;
            font-size: 14px; color: #374151; background: #fff; outline: none; transition: border .2s;
        }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,.08); }
        select.form-control { cursor: pointer; }
        .form-label { font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 5px; display: block; text-transform: uppercase; letter-spacing: .03em; }
        .modal-backdrop {
            display: none; position: fixed; inset: 0; background: rgba(15,23,42,.6);
            z-index: 100; align-items: flex-start; justify-content: center; padding-top: 60px;
        }
        .modal-backdrop.active { display: flex; }
        .modal {
            background: #fff; border-radius: 16px; padding: 28px; width: 100%;
            max-width: 540px; max-height: 85vh; overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0,0,0,.2); animation: slideIn .2s ease;
        }
        @keyframes slideIn { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-title { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert { padding: 12px 16px; border-radius: 10px; font-size: 14px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .badge { padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge-success { background: #dcfce7; color: #16a34a; }
        .badge-danger { background: #fee2e2; color: #dc2626; }
        .badge-warning { background: #fef3c7; color: #d97706; }
        .badge-info { background: #dbeafe; color: #1d4ed8; }
        .badge-gray { background: #f1f5f9; color: #64748b; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .kpi-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .text-success { color: #16a34a; }
        .text-danger { color: #dc2626; }
        .text-muted { color: #94a3b8; font-size: 13px; }
        .separator { border: none; border-top: 1px solid #f1f5f9; margin: 16px 0; }
        @media(max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div style="padding: 18px 16px; border-bottom: 1px solid rgba(255,255,255,.08);">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;background:var(--primary);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa-solid fa-chart-pie" style="color:#fff;font-size:16px;"></i>
                </div>
                <div>
                    <div style="color:#fff;font-weight:700;font-size:15px;">ControleFlex</div>
                    <div style="color:#818cf8;font-size:11px;">Finanças Pessoais</div>
                </div>
            </div>
        </div>

        <nav style="padding: 12px 0; flex: 1;">
            <div style="padding: 8px 16px 4px; font-size:10px; font-weight:800; color:#6366f1; text-transform:uppercase; letter-spacing:.08em;">Visão Geral</div>
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            <a href="{{ route('despesas.index') }}" class="sidebar-link {{ request()->routeIs('despesas.*') ? 'active' : '' }}">
                <i class="fa-solid fa-arrow-trend-down" style="color:#f87171;"></i> Despesas
            </a>
            <a href="{{ route('receitas.index') }}" class="sidebar-link {{ request()->routeIs('receitas.*') ? 'active' : '' }}">
                <i class="fa-solid fa-arrow-trend-up" style="color:#34d399;"></i> Receitas
            </a>
            <a href="{{ route('investimentos.index') }}" class="sidebar-link {{ request()->routeIs('investimentos.*') ? 'active' : '' }}">
                <i class="fa-solid fa-seedling" style="color:#fbbf24;"></i> Investimentos
            </a>

            <div style="padding: 12px 16px 4px; font-size:10px; font-weight:800; color:#6366f1; text-transform:uppercase; letter-spacing:.08em;">Cadastros</div>
            <a href="{{ route('bancos.index') }}" class="sidebar-link {{ request()->routeIs('bancos.*') ? 'active' : '' }}">
                <i class="fa-solid fa-building-columns"></i> Contas Bancárias
            </a>
            <a href="{{ route('familiares.index') }}" class="sidebar-link {{ request()->routeIs('familiares.*') ? 'active' : '' }}">
                <i class="fa-solid fa-users"></i> Familiares
            </a>
            <a href="{{ route('fornecedores.index') }}" class="sidebar-link {{ request()->routeIs('fornecedores.*') ? 'active' : '' }}">
                <i class="fa-solid fa-store"></i> Fornecedores
            </a>
            <a href="{{ route('categorias.index') }}" class="sidebar-link {{ request()->routeIs('categorias.*') ? 'active' : '' }}">
                <i class="fa-solid fa-tags"></i> Categorias
            </a>
        </nav>

        <div style="padding: 12px 8px; border-top: 1px solid rgba(255,255,255,.08);">
            <div style="display:flex;align-items:center;gap:10px;padding: 8px 10px;">
                <div style="width:32px;height:32px;background:rgba(99,102,241,.4);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa-solid fa-user" style="color:#a5b4fc;font-size:13px;"></i>
                </div>
                <div style="min-width:0;">
                    <div style="color:#e2e8f0;font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ Auth::user()->name }}</div>
                    <div style="color:#818cf8;font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ Auth::user()->email }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" style="margin: 4px 8px 0;">
                @csrf
                <button type="submit" class="sidebar-link" style="width:100%;background:rgba(239,68,68,.15);color:#fca5a5;border:none;cursor:pointer;">
                    <i class="fa-solid fa-right-from-bracket"></i> Sair
                </button>
            </form>
        </div>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <h1 style="font-size:16px;font-weight:700;color:#1e293b;">@yield('page-title', 'Dashboard')</h1>
            <div style="display:flex;align-items:center;gap:8px;">
                <a href="{{ route('profile.edit') }}" class="btn-secondary" style="font-size:13px;padding:6px 12px;">
                    <i class="fa-solid fa-gear"></i> Perfil
                </a>
            </div>
        </header>

        <main style="padding: 24px;">
            @if(session('success'))
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger"><i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger" style="flex-direction:column;align-items:flex-start;gap:4px;">
                    <strong><i class="fa-solid fa-triangle-exclamation"></i> Erros encontrados:</strong>
                    <ul style="margin:4px 0 0 16px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
    <script>
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(el => {
                el.style.transition = 'opacity .5s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 5000);

        // Modal helpers
        function openModal(id) {
            document.getElementById(id).classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.style.overflow = '';
        }
        document.addEventListener('click', e => {
            if (e.target.classList.contains('modal-backdrop')) {
                e.target.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    </script>
</body>
</html>
