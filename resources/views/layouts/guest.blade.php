<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AlfaHome') }}</title>
        <link rel="icon" type="image/png" href="/favicon.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-100 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-slate-900 via-slate-800 to-black">
            <div class="mb-6">
                <a href="/" class="flex items-center justify-center"
                   style="padding: 14px 32px; border-radius: 14px;
                          background: radial-gradient(ellipse at center, rgba(20,184,166,.18) 0%, rgba(15,23,42,.0) 70%);
                          box-shadow: 0 0 40px rgba(20,184,166,.12), inset 0 1px 0 rgba(255,255,255,.05);">
                    <img src="/alfa-home-logo.png" alt="AlfaHome" style="height: 38px; width: auto; filter: drop-shadow(0 2px 12px rgba(20,184,166,.35));">
                </a>
            </div>

            <div class="w-full sm:max-w-md px-6 py-8 bg-slate-800/50 backdrop-blur-sm shadow-xl overflow-hidden sm:rounded-xl border border-slate-700/50">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
