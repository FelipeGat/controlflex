<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlfaHome — Manutenção</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .mnt-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 48px 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,.4);
        }

        .mnt-icon {
            font-size: 48px;
            margin-bottom: 20px;
            display: block;
        }

        .mnt-logo {
            font-size: 22px;
            font-weight: 800;
            color: #4f46e5;
            letter-spacing: -0.5px;
            margin-bottom: 24px;
            display: block;
        }

        .mnt-title {
            font-size: 22px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 12px;
        }

        .mnt-msg {
            font-size: 15px;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .mnt-countdown {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 28px;
        }

        .mnt-timer {
            font-size: 22px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            color: #4f46e5;
            letter-spacing: 2px;
        }

        .mnt-footer {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 24px;
        }

        .mnt-login-link {
            font-size: 13px;
            color: #4f46e5;
            text-decoration: none;
        }

        .mnt-login-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="mnt-card">
        <span class="mnt-icon">🔧</span>
        <span class="mnt-logo">AlfaHome</span>

        <h1 class="mnt-title">{{ $titulo ?? 'Sistema em Manutenção' }}</h1>
        <p class="mnt-msg">{{ $mensagem ?? 'Estamos realizando melhorias no sistema. Voltaremos em breve!' }}</p>

        @if($fimProgramado ?? false)
        <div class="mnt-countdown">
            <span>⏱</span>
            <span>Previsão de retorno</span>
            <span class="mnt-timer" id="countdown">--:--:--</span>
        </div>
        @endif

        <p class="mnt-footer">
            Você foi desconectado automaticamente.<br>
            Tente fazer login novamente quando a manutenção terminar.
        </p>

        <a href="{{ route('login') }}" class="mnt-login-link">← Ir para o login</a>
    </div>

    @if($fimProgramado ?? false)
    <script>
        const target = new Date("{{ $fimProgramado }}").getTime();

        function pad(n) { return String(n).padStart(2, '0'); }

        function tick() {
            const diff = Math.max(0, Math.floor((target - Date.now()) / 1000));
            const h = Math.floor(diff / 3600);
            const m = Math.floor((diff % 3600) / 60);
            const s = diff % 60;
            document.getElementById('countdown').textContent = `${pad(h)}:${pad(m)}:${pad(s)}`;

            if (diff <= 0) {
                // Manutenção terminou — recarregar para verificar
                setTimeout(() => window.location.reload(), 3000);
            }
        }

        tick();
        setInterval(tick, 1000);

        // Polling a cada 30s para detectar fim da manutenção
        setInterval(() => window.location.reload(), 30000);
    </script>
    @else
    <script>
        // Polling a cada 30s para detectar fim da manutenção
        setInterval(() => window.location.reload(), 30000);
    </script>
    @endif
</body>
</html>
