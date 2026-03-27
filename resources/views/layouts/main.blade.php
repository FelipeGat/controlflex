<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AlfaHome') }} — @yield('title', 'Dashboard')</title>
    <link rel="icon" type="image/png" href="/favicon.png">
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
            background: var(--color-sidebar);
            display: flex; flex-direction: column;
            z-index: 200;
            transition: width .25s ease, transform .25s ease;
            overflow-x: hidden;
        }
        .sidebar.collapsed { width: var(--sidebar-w-collapsed); }

        .sidebar-logo {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 16px;
            height: var(--topbar-h);
            border-bottom: 1px solid rgba(255,255,255,.06);
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
            background: var(--color-sidebar);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; color: #94a3b8; font-size: 11px;
            z-index: 210;
            transition: background .15s, color .15s, left .25s ease;
            box-shadow: 0 1px 4px rgba(0,0,0,.4);
        }
        .sidebar-collapse-btn:hover { background: var(--color-primary); color: #fff; }
        .sidebar-collapse-btn.collapsed { left: calc(var(--sidebar-w-collapsed) - 12px); }

        .sidebar-section-label {
            padding: 14px 16px 4px;
            font-size: 10px; font-weight: 700;
            color: #475569; text-transform: uppercase; letter-spacing: .07em;
            white-space: nowrap; overflow: hidden;
            transition: opacity .2s;
        }
        .sidebar.collapsed .sidebar-section-label { opacity: 0; height: 0; padding: 0; }

        .sidebar-nav { padding: 6px 8px; flex: 1; overflow-y: auto; overflow-x: hidden; }
        .sidebar-link {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 10px; border-radius: 20px;
            color: #94a3b8; text-decoration: none;
            font-size: 14px; font-weight: 500;
            transition: background .15s, color .15s;
            white-space: nowrap; overflow: hidden;
            position: relative;
        }
        .sidebar-link i { width: 20px; text-align: center; font-size: 14px; flex-shrink: 0; }
        .sidebar-link span { transition: opacity .2s; }
        .sidebar.collapsed .sidebar-link { justify-content: center; }
        .sidebar.collapsed .sidebar-link span { opacity: 0; width: 0; overflow: hidden; }
        .sidebar-link:hover { background: #0f172a; color: #e2e8f0; }
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
            border-top: 1px solid rgba(255,255,255,.06);
            flex-shrink: 0;
        }
        .sidebar-user-info {
            display: flex; align-items: center; gap: 10px;
            padding: 6px 10px; margin-bottom: 4px;
            overflow: hidden;
        }
        .sidebar-user-avatar {
            width: 30px; height: 30px; flex-shrink: 0;
            border-radius: 50%; background: rgba(79,70,229,.4);
            display: flex; align-items: center; justify-content: center;
            color: #a5b4fc; font-size: 12px;
        }
        .sidebar-user-details { transition: opacity .2s; min-width: 0; }
        .sidebar.collapsed .sidebar-user-details { opacity: 0; width: 0; overflow: hidden; }
        .sidebar-user-name { color: #cbd5e1; font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 6px; }
        .sidebar-user-edit { color: #475569; font-size: 11px; flex-shrink: 0; line-height: 1; }
        .sidebar-user-edit:hover { color: var(--color-primary); }
        .sidebar-user-logout { color: #475569; font-size: 11px; flex-shrink: 0; line-height: 1; background:none; border:none; cursor:pointer; padding:0; }
        .sidebar-user-logout:hover { color: #ef4444; }
        .sidebar-user-email { color: #475569; font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

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
            background: #1e293b;
            border-bottom: 1px solid rgba(255,255,255,.06);
            display: flex; align-items: center;
            padding: 0 20px; gap: 12px;
        }
        .topbar-hamburger {
            display: none;
            width: 36px; height: 36px;
            border: none; background: none; cursor: pointer;
            border-radius: 6px; color: #94a3b8;
            align-items: center; justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .topbar-hamburger:hover { background: rgba(255,255,255,.06); }
        .topbar-title {
            flex: 1;
            font-size: 15px; font-weight: 600; color: #e2e8f0;
        }
        .topbar-actions { display: flex; align-items: center; gap: 8px; }
        .topbar-actions .btn-secondary {
            background: rgba(255,255,255,.08); color: #94a3b8;
            border-color: rgba(255,255,255,.1);
        }
        .topbar-actions .btn-secondary:hover { background: rgba(255,255,255,.14); color: #e2e8f0; }

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
            background: #fff; border-top: 1px solid var(--color-border);
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
        <div class="topbar-actions"></div>
    </header>

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
