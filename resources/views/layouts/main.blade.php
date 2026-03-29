<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AlfaHome') }} — @yield('title', 'Dashboard')</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <script>if(localStorage.getItem('alfahome-theme')==='dark')document.documentElement.classList.add('ah-dark-preload');</script>
    <style>.ah-dark-preload body,.ah-dark-preload{background:#0b1120 !important;color:#e2e8f0 !important;}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* ─── Reset & Base ─────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-w: 240px;
            --sidebar-w-collapsed: 70px;
            --topbar-h: 64px;
            --bottom-nav-h: 60px;
            --color-primary: #4f46e5;
            --color-primary-hover: #4338ca;
            --color-success: #16a34a;
            --color-danger: #dc2626;
            --color-warning: #d97706;
            --color-sidebar: #0f172a;
            --color-bg: #f1f5f9;
            --color-border: #e2e8f0;
            --color-text: #1e293b;
            --color-text-muted: #64748b;
            --color-text-subtle: #94a3b8;
            --radius-card: 8px;
            --radius-btn: 6px;
            --radius-badge: 4px;
            --shadow-card: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04);
        }

        html { font-size: 15px; }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: var(--color-bg);
            color: var(--color-text);
            line-height: 1.5;
        }

        /* ─── Sidebar ───────────────────────────────────────────── */
        .sidebar {
            position: fixed; top: 0; left: 0;
            width: var(--sidebar-w); height: 100vh;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            display: flex; flex-direction: column;
            z-index: 200;
            transition: width .25s ease, transform .25s ease, background .22s ease, border-color .22s ease;
            overflow-x: hidden;
            box-shadow: 1px 0 4px rgba(0,0,0,.04);
        }
        .sidebar.collapsed { width: var(--sidebar-w-collapsed); }

        .sidebar-logo {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 16px;
            height: var(--topbar-h);
            border-bottom: 1px solid #e2e8f0;
            border-top: 3px solid var(--color-primary);
            flex-shrink: 0;
            position: relative;
        }
        .sidebar-logo-img {
            height: 34px; width: auto;
            object-fit: contain;
            flex-shrink: 0;
            transition: opacity .2s;
        }
        .sidebar-logo-img-full { display: block; }
        .sidebar-logo-img-icon { display: none; }
        .sidebar.collapsed .sidebar-logo-img-full { display: none; }
        .sidebar.collapsed .sidebar-logo-img-icon { display: block; }

        /* Collapse toggle button */
        .sidebar-collapse-btn {
            position: fixed;
            left: calc(var(--sidebar-w) - 12px);
            top: calc(var(--topbar-h) / 2);
            transform: translateY(-50%);
            width: 24px; height: 24px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; color: #94a3b8; font-size: 11px;
            z-index: 210;
            transition: background .15s, color .15s, border-color .15s, left .25s ease;
            box-shadow: 0 1px 4px rgba(0,0,0,.12);
        }
        .sidebar-collapse-btn:hover { background: var(--color-primary); color: #fff; border-color: var(--color-primary); }
        .sidebar-collapse-btn.collapsed { left: calc(var(--sidebar-w-collapsed) - 12px); }

        .sidebar-section-label {
            padding: 14px 16px 4px;
            font-size: 10px; font-weight: 700;
            color: #94a3b8; text-transform: uppercase; letter-spacing: .07em;
            white-space: nowrap; overflow: hidden;
            transition: opacity .2s, color .22s;
        }
        .sidebar.collapsed .sidebar-section-label { opacity: 0; height: 0; padding: 0; }

        .sidebar-nav { padding: 6px 8px; flex: 1; overflow-y: auto; overflow-x: hidden; }
        .sidebar-link {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 10px; border-radius: 20px;
            color: #64748b; text-decoration: none;
            font-size: 14px; font-weight: 500;
            transition: background .15s, color .15s;
            white-space: nowrap; overflow: hidden;
            position: relative;
        }
        .sidebar-link i { width: 20px; text-align: center; font-size: 14px; flex-shrink: 0; }
        .sidebar-link span { transition: opacity .2s; }
        .sidebar.collapsed .sidebar-link { justify-content: center; }
        .sidebar.collapsed .sidebar-link span { opacity: 0; width: 0; overflow: hidden; }
        .sidebar-link:hover { background: #f1f5f9; color: #1e293b; }
        .sidebar-link.active { background: var(--color-primary); color: #fff; }
        .sidebar-link.active i { color: #fff; }

        /* Tooltip on collapsed */
        .sidebar.collapsed .sidebar-link:hover::after {
            content: attr(data-label);
            position: absolute; left: calc(var(--sidebar-w-collapsed) + 6px); top: 50%; transform: translateY(-50%);
            background: #1e293b; color: #e2e8f0;
            padding: 5px 10px; border-radius: 6px;
            font-size: 13px; white-space: nowrap;
            box-shadow: 0 2px 8px rgba(0,0,0,.3);
            z-index: 300; pointer-events: none;
        }

        .sidebar-user {
            padding: 12px 8px 10px;
            border-top: 1px solid #e2e8f0;
            flex-shrink: 0;
        }
        .sidebar-user-info {
            display: flex; align-items: center; gap: 10px;
            padding: 6px 10px; margin-bottom: 4px;
            overflow: hidden;
        }
        .sidebar-user-avatar {
            width: 30px; height: 30px; flex-shrink: 0;
            border-radius: 50%; background: rgba(79,70,229,.15);
            display: flex; align-items: center; justify-content: center;
            color: var(--color-primary); font-size: 12px;
        }
        .sidebar-user-details { transition: opacity .2s; min-width: 0; }
        .sidebar.collapsed .sidebar-user-details { opacity: 0; width: 0; overflow: hidden; }
        .sidebar-user-name { color: #1e293b; font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 6px; }
        .sidebar-user-edit { color: #94a3b8; font-size: 11px; flex-shrink: 0; line-height: 1; }
        .sidebar-user-edit:hover { color: var(--color-primary); }
        .sidebar-user-logout { color: #94a3b8; font-size: 11px; flex-shrink: 0; line-height: 1; background:none; border:none; cursor:pointer; padding:0; }
        .sidebar-user-logout:hover { color: #ef4444; }
        .sidebar-user-email { color: #94a3b8; font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* ─── Overlay (mobile) ──────────────────────────────────── */
        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 190;
        }
        .sidebar-overlay.active { display: block; }

        /* ─── Main Layout ───────────────────────────────────────── */
        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex; flex-direction: column;
            transition: margin-left .25s ease;
        }
        .main-content.sidebar-collapsed { margin-left: var(--sidebar-w-collapsed); }

        /* ─── Topbar ─────────────────────────────────────────────── */
        .topbar {
            position: sticky; top: 0; z-index: 100;
            height: var(--topbar-h);
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            display: flex; align-items: center;
            padding: 0 20px; gap: 12px;
            transition: background .22s ease, border-color .22s ease, box-shadow .22s ease;
        }
        .topbar-hamburger {
            display: none;
            width: 36px; height: 36px;
            border: none; background: none; cursor: pointer;
            border-radius: 6px; color: #64748b;
            align-items: center; justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
            transition: background .15s, color .15s;
        }
        .topbar-hamburger:hover { background: #f1f5f9; color: #1e293b; }
        .topbar-title {
            flex: 1;
            font-size: 15px; font-weight: 600; color: #1e293b;
            transition: color .2s;
        }
        .topbar-actions { display: flex; align-items: center; gap: 8px; }
        .topbar-actions .btn-secondary {
            background: #f1f5f9; color: #64748b;
            border-color: #e2e8f0;
        }
        .topbar-actions .btn-secondary:hover { background: #e2e8f0; color: #1e293b; }
        .topbar-logo-mobile { display: none; height: 26px; width: auto; object-fit: contain; opacity: .92; }

        /* ─── Botão Tema ─────────────────────────────────────────── */
        .theme-toggle {
            display: flex; align-items: center; justify-content: center;
            width: 36px; height: 36px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f1f5f9;
            color: #64748b;
            cursor: pointer;
            transition: background .15s, border-color .15s, color .15s;
            flex-shrink: 0;
        }
        .theme-toggle:hover { background: #e2e8f0; color: #1e293b; border-color: #cbd5e1; }
        /* Padrão (claro): mostra lua → clicar vai para escuro */
        .theme-toggle .icon-sun  { display: none; }
        .theme-toggle .icon-moon { display: block; }
        /* Dark mode: mostra sol → clicar vai para claro */
        body.dark-mode .theme-toggle .icon-sun  { display: block; }
        body.dark-mode .theme-toggle .icon-moon { display: none; }
        body.dark-mode .theme-toggle { background: rgba(255,255,255,.07); border-color: rgba(255,255,255,.1); color: #94a3b8; }
        body.dark-mode .theme-toggle:hover { background: rgba(255,255,255,.14); color: #e2e8f0; border-color: rgba(255,255,255,.2); }

        /* ═══════════════════════════════════════════════════════════
           DARK MODE COMPLETO — cobre todos os elementos do sistema
           ═══════════════════════════════════════════════════════════ */
        body.dark-mode {
            --color-bg: #0b1120;
            --color-border: #1e2e44;
            --color-text: #e2e8f0;
            --color-text-muted: #94a3b8;
            --color-text-subtle: #64748b;
            --shadow-card: 0 1px 4px rgba(0,0,0,.45), 0 1px 2px rgba(0,0,0,.3);
            background: var(--color-bg);
            color: var(--color-text);
        }

        /* ── Sidebar dark ── */
        body.dark-mode .sidebar {
            background: linear-gradient(180deg, #0e1a2e 0%, #0b1520 100%);
            border-right: 1px solid rgba(255,255,255,.06);
            box-shadow: 2px 0 12px rgba(0,0,0,.35);
        }
        body.dark-mode .sidebar-logo {
            border-bottom-color: rgba(255,255,255,.06);
        }
        body.dark-mode .sidebar-collapse-btn {
            background: #111c2e; border-color: rgba(255,255,255,.15); color: #94a3b8;
            box-shadow: 0 1px 4px rgba(0,0,0,.5);
        }
        body.dark-mode .sidebar-collapse-btn:hover { background: var(--color-primary); border-color: var(--color-primary); color: #fff; }
        body.dark-mode .sidebar-section-label { color: #475569; }
        body.dark-mode .sidebar-link { color: #94a3b8; }
        body.dark-mode .sidebar-link:hover { background: rgba(255,255,255,.06); color: #e2e8f0; }
        body.dark-mode .sidebar-link.active {
            background: var(--color-primary); color: #fff;
            box-shadow: 0 2px 12px rgba(79,70,229,.3);
        }
        body.dark-mode .sidebar.collapsed .sidebar-link:hover::after {
            background: #172135; color: #e2e8f0; border: 1px solid #1e2e44;
        }
        body.dark-mode .sidebar-user { border-top-color: rgba(255,255,255,.06); }
        body.dark-mode .sidebar-user-avatar { background: rgba(79,70,229,.3); color: #a5b4fc; }
        body.dark-mode .sidebar-user-name { color: #cbd5e1; }
        body.dark-mode .sidebar-user-email { color: #475569; }
        body.dark-mode .sidebar-user-edit { color: #475569; }
        body.dark-mode .sidebar-user-logout { color: #475569; }

        /* ── Topbar dark ── */
        body.dark-mode .topbar {
            background: #0e1a2e;
            border-bottom: 1px solid rgba(255,255,255,.06);
            box-shadow: 0 1px 0 rgba(255,255,255,.04), 0 2px 8px rgba(0,0,0,.2);
        }
        body.dark-mode .topbar-title { color: #e2e8f0; }
        body.dark-mode .topbar-hamburger { color: #94a3b8; }
        body.dark-mode .topbar-hamburger:hover { background: rgba(255,255,255,.06); color: #e2e8f0; }
        body.dark-mode .topbar-actions .btn-secondary {
            background: rgba(255,255,255,.06); color: #94a3b8; border-color: rgba(255,255,255,.1);
        }
        body.dark-mode .topbar-actions .btn-secondary:hover { background: rgba(255,255,255,.12); color: #e2e8f0; }

        /* ── Cards ── */
        body.dark-mode .card {
            background: #111c2e;
            border-color: #1e2e44;
            box-shadow: 0 1px 4px rgba(0,0,0,.45);
        }
        body.dark-mode .card-title { color: #e2e8f0; }
        body.dark-mode .card-title i { color: #64748b; }

        /* ── KPIs ── */
        body.dark-mode .kpi-value { color: #e2e8f0; }
        body.dark-mode .kpi-label { color: #94a3b8; }
        body.dark-mode .kpi-sub   { color: #64748b; border-top-color: #1e2e44; }
        body.dark-mode .kpi-icon  { opacity: .9; }

        /* ── Títulos e textos gerais ── */
        body.dark-mode .section-header { color: #e2e8f0; }
        body.dark-mode .section-header h2 { color: #e2e8f0; }
        body.dark-mode .text-muted   { color: #94a3b8; }
        body.dark-mode .text-subtle  { color: #64748b; }
        body.dark-mode .fw-600 { color: #e2e8f0; }
        body.dark-mode p, body.dark-mode span, body.dark-mode label,
        body.dark-mode h1, body.dark-mode h2, body.dark-mode h3,
        body.dark-mode h4, body.dark-mode h5 { color: inherit; }

        /* ── Botões secundários / ghost ── */
        body.dark-mode .btn-secondary {
            background: #172135; color: #94a3b8; border-color: #1e2e44;
        }
        body.dark-mode .btn-secondary:hover { background: #1e2e44; color: #e2e8f0; }
        body.dark-mode .btn-ghost { color: #94a3b8; }
        body.dark-mode .btn-ghost:hover { background: #172135; color: #e2e8f0; }

        /* ── Badges ── */
        body.dark-mode .badge-green, body.dark-mode .badge-success {
            background: rgba(22,163,74,.18); color: #4ade80; }
        body.dark-mode .badge-red, body.dark-mode .badge-danger {
            background: rgba(220,38,38,.18); color: #f87171; }
        body.dark-mode .badge-amber, body.dark-mode .badge-warning, body.dark-mode .badge-yellow {
            background: rgba(217,119,6,.18); color: #fbbf24; }
        body.dark-mode .badge-blue, body.dark-mode .badge-info {
            background: rgba(37,99,235,.18); color: #60a5fa; }
        body.dark-mode .badge-slate, body.dark-mode .badge-gray {
            background: rgba(71,85,105,.22); color: #94a3b8; }
        body.dark-mode .badge-purple {
            background: rgba(109,40,217,.18); color: #a78bfa; }

        /* ── Alertas ── */
        body.dark-mode .alert-success {
            background: rgba(22,163,74,.12); color: #4ade80; border-color: rgba(22,163,74,.3); }
        body.dark-mode .alert-danger {
            background: rgba(220,38,38,.12); color: #f87171; border-color: rgba(220,38,38,.3); }

        /* ── Formulários ── */
        body.dark-mode .form-control {
            background: #0f1826; border-color: #1e2e44;
            color: #e2e8f0;
        }
        body.dark-mode .form-control::placeholder { color: #475569; }
        body.dark-mode .form-control:focus {
            border-color: var(--color-primary); background: #111c2e;
            box-shadow: 0 0 0 3px rgba(79,70,229,.15);
        }
        body.dark-mode select.form-control option { background: #111c2e; color: #e2e8f0; }
        body.dark-mode .form-label { color: #94a3b8; }
        body.dark-mode .form-check { color: #e2e8f0; }

        /* ── Tabelas (.table) ── */
        body.dark-mode .table thead th {
            background: #0f1826; color: #64748b; border-bottom-color: #1e2e44;
        }
        body.dark-mode .table tbody td {
            color: #e2e8f0; border-bottom-color: #172135;
        }
        body.dark-mode .table tbody tr:hover td { background: rgba(255,255,255,.025); }

        /* ── Modal ── */
        body.dark-mode .modal-backdrop { background: rgba(0,0,0,.75); }
        body.dark-mode .modal {
            background: #111c2e; border: 1px solid #1e2e44;
            box-shadow: 0 24px 48px rgba(0,0,0,.6);
        }
        body.dark-mode .modal-header { border-bottom-color: #1e2e44; }
        body.dark-mode .modal-header h3 { color: #e2e8f0; }
        body.dark-mode .modal-close { color: #64748b; }
        body.dark-mode .modal-close:hover { background: #172135; color: #94a3b8; }
        body.dark-mode .modal-body { color: #e2e8f0; }
        body.dark-mode .modal-footer { border-top-color: #1e2e44; }

        /* ── Progress bar ── */
        body.dark-mode .progress-bar { background: #172135; }

        /* ── Empty state ── */
        body.dark-mode .empty-state { color: #64748b; }

        /* ── Bottom nav ── */
        body.dark-mode .bottom-nav { background: #111c2e; border-top-color: #1e2e44; }
        body.dark-mode .bottom-nav a { color: #64748b; }
        body.dark-mode .bottom-nav a.active,
        body.dark-mode .bottom-nav a:hover { color: var(--color-primary); }

        /* ── Extrato (ext-*) ── */
        body.dark-mode .ext-header { border-bottom-color: #1e2e44; }
        body.dark-mode .ext-date-header {
            background: #0f1826; border-color: #1e2e44; }
        body.dark-mode .ext-date-label { color: #64748b; }
        body.dark-mode .ext-row { border-bottom-color: #172135; }
        body.dark-mode .ext-row:hover { background: rgba(255,255,255,.025); }
        body.dark-mode .ext-icone.ext-credito { background: rgba(22,163,74,.15); }
        body.dark-mode .ext-icone.ext-debito  { background: rgba(239,68,68,.15); }
        body.dark-mode .ext-desc { color: #e2e8f0; }
        body.dark-mode .ext-conta-pill { color: #64748b; }
        body.dark-mode .ext-tag-cat { background: #172135; color: #64748b; }
        body.dark-mode .ext-tag-rec { background: rgba(109,40,217,.15); color: #a78bfa; }
        body.dark-mode .ext-tag-doc { background: rgba(3,105,161,.15); color: #60a5fa; }
        body.dark-mode .ext-del-btn  { color: #64748b; }
        body.dark-mode .ext-edit-btn { color: #64748b; }
        body.dark-mode .ext-del-btn:hover  { background: rgba(239,68,68,.15); color: #f87171; }
        body.dark-mode .ext-edit-btn:hover { background: rgba(79,70,229,.15); color: #818cf8; }
        body.dark-mode .ext-footer { background: #0f1826; border-top-color: #1e2e44; }

        /* ── Filtros ── */
        body.dark-mode .filtros-bar { background: #111c2e; border-color: #1e2e44; }
        body.dark-mode .mes-label-btn { color: #e2e8f0; background: transparent; border: none; }
        body.dark-mode .nav-mes-btn { color: #94a3b8; background: #0f1826; }
        body.dark-mode .nav-mes-btn:hover { background: #1e2e44; color: var(--color-primary); }
        body.dark-mode .seg-control { border-color: #1e2e44; }
        body.dark-mode .seg-btn { background: #0f1826; color: #94a3b8; }
        body.dark-mode .seg-btn.ativo { background: var(--color-primary); color: #fff; }
        body.dark-mode .separador-v { background: #1e2e44; }
        body.dark-mode .filtro-grupo { border-bottom-color: #1e2e44; }
        body.dark-mode .av-nome { color: #64748b; }

        /* ── Fluxo de Caixa filtros ── */
        body.dark-mode .fc-atalho-btn:not(.ativo) {
            background: #0f1826; border-color: #1e2e44; color: #94a3b8; }
        body.dark-mode .fc-atalho-btn:not(.ativo):hover { background: #172135; color: #e2e8f0; }
        body.dark-mode .fc-periodo-label { color: #64748b; border-top-color: #1e2e44; }

        /* ── Dashboard saldos ── */
        body.dark-mode .db-section-label { color: #64748b; }
        body.dark-mode .db-section-label::after { background: #1e2e44; }
        body.dark-mode .db-banco-nome { color: #e2e8f0; }
        body.dark-mode .db-banco-tipo { color: #64748b; }
        body.dark-mode .db-banco-item { border-bottom-color: #172135; }

        /* ── Investimentos KPI ── */
        body.dark-mode .inv-kpi-row .card { background: #111c2e; border-color: #1e2e44; }

        /* ── Fluxo de Caixa KPI ── */
        body.dark-mode .fc-kpi-grid .card { background: #111c2e; border-color: #1e2e44; }

        /* ── Sidebar tooltip (collapsed) ── */
        body.dark-mode .sidebar.collapsed .sidebar-link:hover::after {
            background: #172135; color: #e2e8f0; border: 1px solid #1e2e44; }

        /* ── Paginação ── */
        body.dark-mode nav[role="navigation"] { color: #94a3b8; }
        body.dark-mode nav[role="navigation"] a,
        body.dark-mode nav[role="navigation"] button { color: #94a3b8; border-color: #1e2e44; background: #111c2e; }
        body.dark-mode nav[role="navigation"] span[aria-current] { background: var(--color-primary); color: #fff; border-color: var(--color-primary); }

        /* ── Transições suaves de tema ── */
        body, .card, .topbar, .modal, .form-control, .btn-secondary,
        .table thead th, .table tbody td, .bottom-nav,
        .ext-row, .ext-date-header, .ext-footer, .filtros-bar,
        .mes-label-btn, .nav-mes-btn, .seg-btn, .progress-bar {
            transition: background-color .22s ease, border-color .22s ease, color .18s ease;
        }

        /* ─── Page content ──────────────────────────────────────── */
        .page-content {
            padding: 20px;
            flex: 1;
            overflow-x: hidden;
            min-width: 0;
        }

        /* ─── Cards ─────────────────────────────────────────────── */
        .card {
            background: #fff;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-card);
            padding: 16px;
            box-shadow: var(--shadow-card);
        }
        .card-title {
            font-size: 14px; font-weight: 600; color: var(--color-text);
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 14px;
        }
        .card-title i { color: var(--color-text-subtle); font-size: 13px; }

        /* ─── KPI ────────────────────────────────────────────────── */
        .kpi-label { font-size: 12px; font-weight: 500; color: var(--color-text-muted); margin-bottom: 4px; }
        .kpi-value { font-size: 1.6rem; font-weight: 700; line-height: 1.1; }
        .kpi-sub { font-size: 12px; color: var(--color-text-muted); margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--color-border); }
        .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }

        /* ─── Buttons ────────────────────────────────────────────── */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; font-size: 13px; font-weight: 500;
            border-radius: var(--radius-btn); border: none;
            cursor: pointer; text-decoration: none;
            transition: background .15s, opacity .15s;
            white-space: nowrap;
        }
        .btn-primary { background: var(--color-primary); color: #fff; }
        .btn-primary:hover { background: var(--color-primary-hover); color: #fff; }
        .btn-success { background: #16a34a; color: #fff; }
        .btn-success:hover { background: #15803d; color: #fff; }
        .btn-amber { background: #d97706; color: #fff; }
        .btn-amber:hover { background: #b45309; color: #fff; }
        .btn-secondary {
            background: #fff; color: var(--color-text-muted);
            border: 1px solid var(--color-border);
        }
        .btn-secondary:hover { background: var(--color-bg); }
        .btn-danger { background: var(--color-danger); color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-ghost {
            background: none; border: none; cursor: pointer;
            color: var(--color-text-muted); padding: 5px 8px;
            border-radius: var(--radius-btn); font-size: 13px;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-ghost:hover { background: var(--color-bg); color: var(--color-text); }
        .btn-sm { padding: 4px 9px; font-size: 12px; }
        .btn-icon { padding: 6px 8px; }
        .btn-icon.btn-sm { padding: 4px 6px; }

        /* ─── Badges ─────────────────────────────────────────────── */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 2px 7px; border-radius: var(--radius-badge);
            font-size: 11px; font-weight: 600;
        }
        .badge-green { background: #dcfce7; color: #16a34a; }
        .badge-red { background: #fee2e2; color: #dc2626; }
        .badge-amber { background: #fef3c7; color: #92400e; }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .badge-slate { background: #f1f5f9; color: #475569; }
        .badge-purple { background: #ede9fe; color: #6d28d9; }
        /* legacy aliases */
        .badge-success { background: #dcfce7; color: #16a34a; }
        .badge-danger { background: #fee2e2; color: #dc2626; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1d4ed8; }
        .badge-gray { background: #f1f5f9; color: #475569; }

        /* ─── Alerts ─────────────────────────────────────────────── */
        .alert {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 12px 14px; border-radius: 6px;
            font-size: 13px; margin-bottom: 16px;
        }
        .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert i { margin-top: 1px; flex-shrink: 0; }

        /* ─── Forms ──────────────────────────────────────────────── */
        .form-group { display: flex; flex-direction: column; gap: 4px; }
        .form-label {
            font-size: 11px; font-weight: 600; color: var(--color-text-muted);
            text-transform: uppercase; letter-spacing: .04em;
        }
        .form-control {
            width: 100%; padding: 8px 10px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-btn); font-size: 13px;
            color: var(--color-text); background: #fff; outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-control:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(79,70,229,.1);
        }
        select.form-control { cursor: pointer; }
        textarea.form-control { resize: vertical; }

        /* ─── Table ──────────────────────────────────────────────── */
        .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .table thead th {
            padding: 9px 12px; text-align: left;
            font-size: 11px; font-weight: 600; color: var(--color-text-subtle);
            text-transform: uppercase; letter-spacing: .04em;
            border-bottom: 1px solid var(--color-border);
            background: #fafafa; white-space: nowrap;
        }
        .table tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #f8fafc;
            color: var(--color-text); vertical-align: middle;
        }
        .table tbody tr:last-child td { border-bottom: none; }
        .table tbody tr:hover td { background: #fafbfc; }

        /* ─── Grids ──────────────────────────────────────────────── */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }

        /* ─── Modal ──────────────────────────────────────────────── */
        .modal-backdrop {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.5); z-index: 300;
            align-items: flex-start; justify-content: center;
            padding: 48px 16px 16px;
            overflow-y: auto;
        }
        .modal-backdrop.active { display: flex; }
        .modal {
            background: #fff; border-radius: 10px;
            width: 100%; max-width: 520px;
            box-shadow: 0 20px 40px rgba(0,0,0,.15);
            animation: modal-in .18s ease;
            flex-shrink: 0;
        }
        @keyframes modal-in { from { transform: translateY(-16px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header {
            display: flex; align-items: center; gap: 10px;
            padding: 16px 20px; border-bottom: 1px solid var(--color-border);
        }
        .modal-header h3 { font-size: 15px; font-weight: 600; flex: 1; color: var(--color-text); }
        .modal-close {
            width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;
            border: none; background: none; cursor: pointer;
            color: var(--color-text-subtle); border-radius: 4px; font-size: 18px; line-height: 1;
        }
        .modal-close:hover { background: var(--color-bg); }
        .modal-body { padding: 20px; }
        .modal-footer { display: flex; gap: 8px; justify-content: flex-end; padding-top: 16px; }

        /* ─── Form grid in modal ─────────────────────────────────── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .form-grid .span-2 { grid-column: span 2; }
        .form-check { display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; }
        .form-check input { cursor: pointer; }

        /* ─── Utils ──────────────────────────────────────────────── */
        .text-green { color: var(--color-success); }
        .text-red { color: var(--color-danger); }
        .text-amber { color: var(--color-warning); }
        .text-muted { color: var(--color-text-muted); }
        .text-subtle { color: var(--color-text-subtle); }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'SF Mono', 'Fira Code', monospace; }
        .fw-600 { font-weight: 600; }
        .fw-700 { font-weight: 700; }
        .mt-1 { margin-top: 4px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 12px; }
        .mt-4 { margin-top: 16px; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 12px; }
        .mb-4 { margin-bottom: 16px; }
        .mb-5 { margin-bottom: 24px; }
        .gap-2 { gap: 8px; }
        .d-flex { display: flex; }
        .align-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .flex-wrap { flex-wrap: wrap; }
        .overflow-hidden { overflow: hidden; }

        /* progress bar */
        .progress-bar { height: 5px; background: var(--color-border); border-radius: 999px; overflow: hidden; }
        .progress-bar-fill { height: 100%; border-radius: 999px; transition: width .3s; }

        /* Logout button (looks like sidebar-link) */
        .sidebar-logout {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px; border-radius: 6px; width: 100%;
            background: none; border: none; cursor: pointer;
            color: #64748b; font-size: 14px; font-weight: 500;
            transition: background .15s, color .15s;
        }
        .sidebar-logout:hover { background: rgba(220,38,38,.15); color: #fca5a5; }
        .sidebar-logout i { width: 16px; text-align: center; font-size: 14px; }

        /* ─── Bottom navigation (mobile only) ───────────────────── */
        .bottom-nav {
            display: none;
            position: fixed; bottom: 0; left: 0; right: 0;
            height: var(--bottom-nav-h);
            background: #ffffff; border-top: 1px solid #e2e8f0;
            transition: background .22s ease, border-color .22s ease;
            z-index: 180;
        }
        .bottom-nav ul {
            display: flex; height: 100%; list-style: none;
        }
        .bottom-nav li { flex: 1; }
        .bottom-nav a {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            height: 100%; gap: 3px; text-decoration: none;
            color: var(--color-text-subtle); font-size: 10px; font-weight: 500;
            transition: color .15s;
        }
        .bottom-nav a i { font-size: 18px; }
        .bottom-nav a.active,
        .bottom-nav a:hover { color: var(--color-primary); }

        /* Chart container */
        .chart-box { position: relative; height: 260px; }
        .chart-box-sm { position: relative; height: 220px; }

        /* ─── Section header ─────────────────────────────────────── */
        .section-header {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 10px; margin-bottom: 16px;
        }
        .section-header h2 { font-size: 15px; font-weight: 600; }

        /* ─── Empty state ────────────────────────────────────────── */
        .empty-state { text-align: center; padding: 40px 20px; color: var(--color-text-subtle); }
        .empty-state i { font-size: 36px; display: block; margin-bottom: 12px; opacity: .5; }
        .empty-state p { font-size: 13px; }

        /* ─── Badge yellow (alias) ───────────────────────────────── */
        .badge-yellow { background: #fef3c7; color: #92400e; }

        /* ─── Extrato / List rows (shared across pages) ─────────── */
        .ext-card { padding:0; overflow:hidden; }
        .ext-header { display:flex; justify-content:space-between; align-items:center; padding:13px 16px; border-bottom:1px solid #f1f5f9; }
        .ext-date-header { display:flex; align-items:center; justify-content:space-between; padding:6px 16px; background:#f8fafc; border-top:1px solid #f1f5f9; border-bottom:1px solid #f1f5f9; position:sticky; top:0; z-index:2; }
        .ext-date-label { font-size:10px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; }
        .ext-row { display:flex; align-items:center; gap:12px; padding:11px 16px; border-bottom:1px solid #f8fafc; transition:background .12s; position:relative; }
        .ext-row:hover { background:#fafbff; }
        .ext-row:last-of-type { border-bottom:none; }
        .ext-row::before { content:''; position:absolute; left:0; top:8px; bottom:8px; width:3px; border-radius:0 3px 3px 0; }
        .ext-credito::before { background:#16a34a; }
        .ext-debito::before  { background:#ef4444; }
        .ext-icone { width:40px; height:40px; border-radius:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .ext-icone.ext-credito { background:#f0fdf4; }
        .ext-icone.ext-debito  { background:#fff1f2; }
        .ext-info { flex:1; min-width:0; }
        .ext-desc { font-size:13px; font-weight:600; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; line-height:1.3; }
        .ext-meta { display:flex; align-items:center; gap:5px; margin-top:4px; flex-wrap:wrap; }
        .ext-conta-pill { display:inline-flex; align-items:center; gap:4px; font-size:11px; color:#94a3b8; }
        .ext-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
        .ext-tag { font-size:10px; padding:1px 7px; border-radius:20px; font-weight:600; line-height:1.6; white-space:nowrap; }
        .ext-tag-cat { background:#f1f5f9; color:#475569; }
        .ext-tag-rec { background:#ede9fe; color:#7c3aed; }
        .ext-tag-doc { background:#e0f2fe; color:#0369a1; }
        .ext-valor-col { text-align:right; flex-shrink:0; min-width:100px; }
        .ext-valor { font-size:14px; font-weight:700; white-space:nowrap; line-height:1.2; }
        .ext-valor.ext-credito { color:#16a34a; }
        .ext-valor.ext-debito  { color:#ef4444; }
        .ext-status { font-size:10px; font-weight:600; margin-top:3px; display:flex; align-items:center; justify-content:flex-end; gap:3px; }
        .s-ok   { color:#16a34a; }
        .s-venc { color:#ef4444; }
        .s-pend { color:#d97706; }
        .ext-actions { display:flex; align-items:center; gap:2px; flex-shrink:0; }
        .ext-del-btn, .ext-edit-btn { opacity:0; transition:opacity .15s; background:none; border:none; cursor:pointer; padding:6px; border-radius:6px; line-height:1; }
        .ext-del-btn  { color:#94a3b8; }
        .ext-edit-btn { color:#94a3b8; }
        .ext-del-btn:hover  { background:#fff1f2; color:#ef4444; }
        .ext-edit-btn:hover { background:#f0f9ff; color:var(--color-primary); }
        .ext-row:hover .ext-del-btn,
        .ext-row:hover .ext-edit-btn { opacity:1; }
        .ext-footer { display:flex; align-items:center; gap:16px; padding:10px 16px; background:#f8fafc; border-top:2px solid #f1f5f9; }
        .ext-footer-item { display:flex; align-items:center; gap:6px; }
        .ext-footer-dot  { width:8px; height:8px; border-radius:50%; }
        @media (max-width:640px) {
            .ext-row { padding:10px 14px 10px 18px; gap:10px; }
            .ext-icone { width:36px; height:36px; border-radius:9px; }
            .ext-desc { font-size:12px; }
            .ext-valor { font-size:13px; }
            .ext-valor-col { min-width:80px; }
            .ext-del-btn, .ext-edit-btn { opacity:1; }
            .ext-tag-cat { display:none; }
        }

        /* ─── Filter Bar (shared across pages) ───────────────────── */
        .filtros-bar  { padding:12px 16px; margin-bottom:18px; }
        .filtros-lanc { display:flex; align-items:center; gap:10px; justify-content:space-between; }
        .filtro-grupo { display:flex; align-items:center; gap:8px; flex-shrink:0; }
        .filtro-grupo-centro { justify-content:center; }

        /* Avatares */
        .av-grupo   { display:flex; align-items:flex-start; gap:6px; flex-wrap:wrap; }
        .av-item    { display:flex; flex-direction:column; align-items:center; gap:3px; text-decoration:none; flex-shrink:0; transition:opacity .15s; }
        .av-item:hover { opacity:.8; }
        .av-circulo { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; overflow:hidden; transition:all .2s; flex-shrink:0; }
        .av-nome    { font-size:9.5px; white-space:nowrap; max-width:42px; overflow:hidden; text-overflow:ellipsis; color:#94a3b8; text-align:center; }

        /* Month nav */
        .nav-mes-btn { display:flex; align-items:center; justify-content:center; width:36px; height:36px; color:#64748b; text-decoration:none; background:#fff; transition:background .15s; }
        .nav-mes-btn:hover { background:#f1f5f9; color:var(--color-primary); }
        .mes-label-btn { padding:6px 16px; font-weight:700; font-size:13px; color:var(--color-text); white-space:nowrap; cursor:pointer; user-select:none; background:#fff; border:none; font-family:inherit; }

        /* Segmented control */
        .seg-control { display:flex; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden; }
        .seg-btn { padding:6px 13px; font-size:12px; font-weight:600; border:none; cursor:pointer; white-space:nowrap; transition:background .15s, color .15s; }

        /* Vertical separator */
        .separador-v { width:1px; height:30px; background:#e2e8f0; flex-shrink:0; align-self:center; }

        /* Mobile — shared filter rules */
        @media (max-width:640px) {
            .filtros-lanc       { flex-direction:column; align-items:stretch; gap:0; }
            .filtro-grupo       { width:100%; padding:10px 0; border-bottom:1px solid #f1f5f9; }
            .filtro-grupo:last-child { border-bottom:none; padding-bottom:2px; }
            .filtro-grupo-centro  { justify-content:space-between; }
            .filtro-grupo-members { justify-content:center; order:4; }
            .filtro-grupo-bancos  { order:3; }
            .filtro-grupo-tipo    { order:2; }
            .av-grupo  { flex-wrap:nowrap; overflow-x:auto; scrollbar-width:none; -ms-overflow-style:none; gap:10px; padding-bottom:2px; justify-content:center; }
            .av-grupo::-webkit-scrollbar { display:none; }
            .av-circulo { width:40px; height:40px; }
            .av-nome    { font-size:10px; max-width:44px; }
            .separador-v { display:none !important; }
            .seg-btn { padding:8px 4px; font-size:13px; }
        }

        /* ─── Responsive ──────────────────────────────────────────── */
        @media (max-width: 1024px) {
            .grid-4 { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: var(--sidebar-w) !important; height: 100vh; height: 100dvh; }
            .sidebar.open { transform: translateX(0); }
            .sidebar-nav { padding: 4px 8px; }
            .sidebar-link { padding: 8px 10px; font-size: 13px; }
            .sidebar-section-label { padding: 10px 16px 2px; font-size: 9px; }
            .sidebar-user { padding: 8px 8px 6px; }
            .sidebar-user-info { padding: 4px 10px; margin-bottom: 2px; }
            .main-content { margin-left: 0 !important; }
            .sidebar-collapse-btn { display: none; }
            .topbar-hamburger { display: flex; }
            .topbar-logo-mobile { display: block; }
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
            .grid-4 { grid-template-columns: 1fr 1fr; }
            .page-content { padding: 14px 12px calc(var(--bottom-nav-h) + 14px); }
            .bottom-nav { display: block; }
            .bottom-nav ul { padding: 0 4px; }
            .bottom-nav a { font-size: 9px; gap: 2px; }
            .bottom-nav a i { font-size: 16px; }
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .span-2 { grid-column: span 1; }
            /* Hide some table columns on mobile */
            .table .hide-mobile { display: none; }
            .hide-mobile { display: none !important; }
            .modal-backdrop { padding: 0; align-items: flex-end; }
            .modal { border-radius: 14px 14px 0 0; max-width: 100%; max-height: 92vh; overflow-y: auto; }
            .modal-body { padding: 16px; }
            /* Section header — centered on mobile */
            .section-header {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            .section-header h2 { font-size: 14px; text-align: center; }
            .section-header .btn { width: 100%; justify-content: center; }
            .section-header form { width: 100%; }
            .section-header form .d-flex { justify-content: center; }
            /* Table action buttons wrap on mobile */
            .table .actions-cell { flex-wrap: wrap; }
            /* Date inputs shrink on mobile */
            input[type="date"].form-control { min-width: 0; width: 100%; }
            /* KPI smaller on mobile */
            .kpi-value { font-size: 1.3rem; }
            /* Permissions grid scroll */
            .perm-grid-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .perm-grid-wrapper table { min-width: 480px; }
            /* Charts smaller on mobile */
            .chart-box { height: 220px; }
            .chart-box-sm { height: 180px; }
        }
        @media (max-width: 480px) {
            .grid-4 { grid-template-columns: 1fr; }
            .topbar { padding: 0 12px; gap: 10px; }
            .kpi-value { font-size: 1.1rem; }
            .card { padding: 12px; }
            .btn { padding: 8px 12px; font-size: 12px; }
            .btn-sm { padding: 6px 10px; font-size: 11px; }
            /* Sidebar even more compact */
            .sidebar-link { padding: 7px 10px; font-size: 12px; gap: 8px; }
            .sidebar-link i { font-size: 13px; }
            .sidebar-section-label { padding: 8px 16px 2px; font-size: 9px; }
            .sidebar-user { padding: 6px 8px 4px; }
            /* Modal adjustments for small phones */
            .modal-header { padding: 14px 16px; }
            .modal-header h3 { font-size: 14px; }
            .modal-body { padding: 14px; }
            .modal-footer { flex-wrap: wrap; }
            .modal-footer .btn { flex: 1; min-width: 0; justify-content: center; }
            /* Table tighter on small phones */
            .table thead th { padding: 7px 8px; font-size: 10px; }
            .table tbody td { padding: 8px; font-size: 12px; }
            /* Badge smaller */
            .badge { font-size: 10px; padding: 2px 5px; }
            /* Bottom nav safe area for notch phones */
            .bottom-nav { padding-bottom: env(safe-area-inset-bottom, 0); height: calc(var(--bottom-nav-h) + env(safe-area-inset-bottom, 0)); }
            .page-content { padding-bottom: calc(var(--bottom-nav-h) + env(safe-area-inset-bottom, 0) + 14px); }
            /* KPI icon smaller */
            .kpi-icon { width: 34px; height: 34px; font-size: 14px; }
            /* Form controls tighter */
            .form-control { padding: 7px 9px; font-size: 13px; }
            .form-label { font-size: 10px; }
        }
    </style>
    @stack('styles')
</head>
<body>
<script>if(localStorage.getItem('alfahome-theme')==='dark')document.body.classList.add('dark-mode');</script>

{{-- ─── Sidebar ──────────────────────────────────────────────────────── --}}
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <img src="/alfa-home-logo.png" alt="AlfaHome" class="sidebar-logo-img sidebar-logo-img-full" style="max-width: 140px;">
        <img src="/alfa-home-logo-2.png" alt="AlfaHome" class="sidebar-logo-img sidebar-logo-img-icon" style="height: 34px; width: auto;">
    </div>

    <nav class="sidebar-nav">
        @if(Auth::user()->isSuperAdmin())
        {{-- ─── Sidebar Super Admin ──────────────────────────────────────── --}}
        <div class="sidebar-section-label">Painel SaaS</div>
        <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" data-label="Dashboard">
            <i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span>
        </a>
        <a href="{{ route('admin.revendas.index') }}" class="sidebar-link {{ request()->routeIs('admin.revendas.*') ? 'active' : '' }}" data-label="Revendas">
            <i class="fa-solid fa-building"></i> <span>Revendas</span>
        </a>
        <a href="{{ route('admin.planos.index') }}" class="sidebar-link {{ request()->routeIs('admin.planos.*') ? 'active' : '' }}" data-label="Planos">
            <i class="fa-solid fa-credit-card"></i> <span>Planos</span>
        </a>

        @elseif(Auth::user()->isAdminRevenda())
        {{-- ─── Sidebar Admin Revenda ────────────────────────────────────── --}}
        <div class="sidebar-section-label">Minha Revenda</div>
        <a href="{{ route('revenda.dashboard') }}" class="sidebar-link {{ request()->routeIs('revenda.dashboard') ? 'active' : '' }}" data-label="Dashboard">
            <i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span>
        </a>
        <a href="{{ route('revenda.clientes.index') }}" class="sidebar-link {{ request()->routeIs('revenda.clientes.*') ? 'active' : '' }}" data-label="Clientes">
            <i class="fa-solid fa-store"></i> <span>Clientes</span>
        </a>

        @else
        {{-- ─── Sidebar Tenant (Master/Membro) ──────────────────────────── --}}
        <div class="sidebar-section-label">Visão Geral</div>
        <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" data-label="Dashboard">
            <i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span>
        </a>
        <a href="{{ route('alertas.index') }}" class="sidebar-link {{ request()->routeIs('alertas.*') ? 'active' : '' }}" data-label="Alertas" style="position:relative;">
            <i class="fa-solid fa-bell"></i>
            <span>Alertas</span>
        </a>
        <a href="{{ route('fluxo-caixa.index') }}" class="sidebar-link {{ request()->routeIs('fluxo-caixa.*') ? 'active' : '' }}" data-label="Contas a Pagar/Receber">
            <i class="fa-solid fa-arrows-left-right"></i> <span>Contas / Baixas</span>
        </a>

        <div class="sidebar-section-label">Lançamentos</div>
        @if(Auth::user()->temPermissao('despesas', 'criar'))
        <a href="{{ route('lancamentos.index') }}" class="sidebar-link {{ request()->routeIs('lancamentos.*') ? 'active' : '' }}" data-label="Lançamentos">
            <i class="fa-solid fa-list-ul"></i> <span>Lançamentos</span>
        </a>
        @endif
        @if(Auth::user()->temPermissao('despesas', 'ver'))
        <a href="{{ route('despesas.index') }}" class="sidebar-link {{ request()->routeIs('despesas.*') ? 'active' : '' }}" data-label="Despesas">
            <i class="fa-solid fa-arrow-trend-down"></i> <span>Despesas</span>
        </a>
        @endif
        @if(Auth::user()->temPermissao('receitas', 'ver'))
        <a href="{{ route('receitas.index') }}" class="sidebar-link {{ request()->routeIs('receitas.*') ? 'active' : '' }}" data-label="Receitas">
            <i class="fa-solid fa-arrow-trend-up"></i> <span>Receitas</span>
        </a>
        @endif
        @if(Auth::user()->temPermissao('investimentos', 'ver'))
        <a href="{{ route('investimentos.index') }}" class="sidebar-link {{ request()->routeIs('investimentos.*') ? 'active' : '' }}" data-label="Investimentos">
            <i class="fa-solid fa-seedling"></i> <span>Investimentos</span>
        </a>
        @endif

        <div class="sidebar-section-label">Cadastros</div>
        @if(Auth::user()->temPermissao('bancos', 'ver'))
        <a href="{{ route('bancos.index') }}" class="sidebar-link {{ request()->routeIs('bancos.*') ? 'active' : '' }}" data-label="Contas Bancárias">
            <i class="fa-solid fa-building-columns"></i> <span>Contas Bancárias</span>
        </a>
        @endif
        @if(Auth::user()->temPermissao('familiares', 'ver'))
        <a href="{{ route('familiares.index') }}" class="sidebar-link {{ request()->routeIs('familiares.*') ? 'active' : '' }}" data-label="Membros">
            <i class="fa-solid fa-users"></i> <span>Membros</span>
        </a>
        @endif
        @if(Auth::user()->temPermissao('fornecedores', 'ver'))
        <a href="{{ route('fornecedores.index') }}" class="sidebar-link {{ request()->routeIs('fornecedores.*') ? 'active' : '' }}" data-label="Fornecedores">
            <i class="fa-solid fa-store"></i> <span>Fornecedores</span>
        </a>
        @endif
        @if(Auth::user()->temPermissao('categorias', 'ver'))
        <a href="{{ route('categorias.index') }}" class="sidebar-link {{ request()->routeIs('categorias.*') ? 'active' : '' }}" data-label="Categorias">
            <i class="fa-solid fa-tags"></i> <span>Categorias</span>
        </a>
        @endif
        @endif
    </nav>

    <div class="sidebar-user">
        <div class="sidebar-user-info">
            <div class="sidebar-user-avatar">
                @if(Auth::user()->foto)
                    <img src="{{ asset('storage/' . Auth::user()->foto) }}" alt="Foto" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                @else
                    <i class="fa-solid fa-user"></i>
                @endif
            </div>
            <div class="sidebar-user-details">
                <div class="sidebar-user-name">
                    {{ Auth::user()->name }}
                    <a href="{{ route('profile.edit') }}" class="sidebar-user-edit" title="Editar perfil">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" style="display:inline; margin-left:2px;">
                        @csrf
                        <button type="submit" class="sidebar-user-logout" title="Sair">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
                <div class="sidebar-user-email">{{ Auth::user()->email }}</div>
            </div>
        </div>
    </div>
</aside>

{{-- ─── Collapse button (outside sidebar to avoid overflow-x clipping) ── --}}
<button class="sidebar-collapse-btn" id="sidebar-collapse-btn" title="Recolher menu">
    <i class="fa-solid fa-chevron-left" id="sidebar-collapse-icon"></i>
</button>

{{-- ─── Mobile overlay ────────────────────────────────────────────────── --}}
<div class="sidebar-overlay" id="sidebar-overlay"></div>

{{-- ─── Main content ──────────────────────────────────────────────────── --}}
<div class="main-content" id="main-content">
    <header class="topbar">
        <button class="topbar-hamburger" id="hamburger" aria-label="Menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <span class="topbar-title">@yield('page-title', 'Dashboard')</span>
        <div class="topbar-actions">
            <button class="theme-toggle" id="theme-toggle" title="Alternar tema" aria-label="Alternar modo claro/escuro">
                <i class="fa-solid fa-sun  icon-sun"  style="font-size:15px;"></i>
                <i class="fa-solid fa-moon icon-moon" style="font-size:15px;"></i>
            </button>
            <img src="/alfa-home-logo-2.png" alt="AlfaHome" class="topbar-logo-mobile">
        </div>
    </header>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var btn = document.getElementById('theme-toggle');
            if (!btn) return;
            btn.addEventListener('click', function () {
                var isDark = document.body.classList.toggle('dark-mode');
                localStorage.setItem('alfahome-theme', isDark ? 'dark' : 'light');
                /* Atualiza cores padrão do Chart.js para os gráficos já renderizados */
                if (typeof Chart !== 'undefined') {
                    var tickColor  = isDark ? '#64748b' : '#94a3b8';
                    var gridColor  = isDark ? '#1e2e44' : '#f1f5f9';
                    Chart.defaults.color = tickColor;
                    Chart.defaults.borderColor = gridColor;
                    /* Re-renderiza todos os charts registrados */
                    Object.values(Chart.instances || {}).forEach(function(c) {
                        if (c.options.scales) {
                            Object.values(c.options.scales).forEach(function(s) {
                                if (s.ticks)  s.ticks.color  = tickColor;
                                if (s.grid)   s.grid.color   = gridColor;
                            });
                        }
                        if (c.options.plugins && c.options.plugins.legend && c.options.plugins.legend.labels)
                            c.options.plugins.legend.labels.color = tickColor;
                        c.update('none');
                    });
                }
            });
        });
    </script>

    <main class="page-content">
        @if(session('success'))
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-xmark"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" style="flex-direction:column;align-items:flex-start;">
                <strong><i class="fa-solid fa-triangle-exclamation"></i> Verifique os erros:</strong>
                <ul style="margin-top:6px;padding-left:16px;font-size:13px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>

{{-- ─── Bottom navigation (mobile) ───────────────────────────────────── --}}
<nav class="bottom-nav" aria-label="Navegação mobile">
    <ul>
        @if(Auth::user()->isSuperAdmin())
            <li>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('admin.revendas.index') }}" class="{{ request()->routeIs('admin.revendas.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-building"></i> Revendas
                </a>
            </li>
            <li>
                <a href="{{ route('admin.planos.index') }}" class="{{ request()->routeIs('admin.planos.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-tags"></i> Planos
                </a>
            </li>
        @elseif(Auth::user()->isAdminRevenda())
            <li>
                <a href="{{ route('revenda.dashboard') }}" class="{{ request()->routeIs('revenda.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-pie"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('revenda.clientes.index') }}" class="{{ request()->routeIs('revenda.clientes.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-store"></i> Clientes
                </a>
            </li>
            <li>
                <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-user"></i> Perfil
                </a>
            </li>
        @else
            <li>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
            </li>
            @if(Auth::user()->temPermissao('despesas', 'criar'))
            <li>
                <a href="{{ route('lancamentos.index') }}" class="{{ request()->routeIs('lancamentos.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-list-ul"></i> Lançamentos
                </a>
            </li>
            @endif
            @if(Auth::user()->temPermissao('despesas', 'ver'))
            <li>
                <a href="{{ route('despesas.index') }}" class="{{ request()->routeIs('despesas.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-arrow-trend-down"></i> Despesas
                </a>
            </li>
            @endif
            @if(Auth::user()->temPermissao('receitas', 'ver'))
            <li>
                <a href="{{ route('receitas.index') }}" class="{{ request()->routeIs('receitas.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-arrow-trend-up"></i> Receitas
                </a>
            </li>
            @endif
            @if(Auth::user()->temPermissao('bancos', 'ver'))
            <li>
                <a href="{{ route('bancos.index') }}" class="{{ request()->routeIs('bancos.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-building-columns"></i> Contas
                </a>
            </li>
            @endif
        @endif
    </ul>
</nav>

@stack('scripts')
<script>
    const sidebar     = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const overlay     = document.getElementById('sidebar-overlay');
    const hamburger   = document.getElementById('hamburger');
    const collapseBtn = document.getElementById('sidebar-collapse-btn');
    const collapseIcon = document.getElementById('sidebar-collapse-icon');

    // ── Desktop collapse (persisted) ──────────────────────────────
    const STORAGE_KEY = 'alfahome_sidebar_collapsed';

    function applyCollapse(collapsed) {
        if (collapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('sidebar-collapsed');
            collapseBtn.classList.add('collapsed');
            collapseIcon.classList.replace('fa-chevron-left', 'fa-chevron-right');
        } else {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('sidebar-collapsed');
            collapseBtn.classList.remove('collapsed');
            collapseIcon.classList.replace('fa-chevron-right', 'fa-chevron-left');
        }
    }

    // Load persisted state (desktop only)
    if (window.innerWidth > 768) {
        applyCollapse(localStorage.getItem(STORAGE_KEY) === 'true');
    }

    collapseBtn.addEventListener('click', () => {
        const collapsed = !sidebar.classList.contains('collapsed');
        applyCollapse(collapsed);
        localStorage.setItem(STORAGE_KEY, collapsed);
    });

    // ── Mobile open/close ─────────────────────────────────────────
    function openSidebar()  { sidebar.classList.add('open');  overlay.classList.add('active');  document.body.style.overflow = 'hidden'; }
    function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow = ''; }

    hamburger.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
    overlay.addEventListener('click', closeSidebar);

    // Close sidebar when a link is clicked on mobile
    sidebar.querySelectorAll('.sidebar-link, .sidebar-logout').forEach(el => {
        el.addEventListener('click', () => { if (window.innerWidth <= 768) closeSidebar(); });
    });

    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(el => {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 400);
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
