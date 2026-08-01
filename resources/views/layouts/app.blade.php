<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Manifest') · Shopify CSV Import</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: {
              sans: ['Inter', 'sans-serif'],
              mono: ['JetBrains Mono', 'monospace'],
            },
            colors: {
              hull: '#0B1220',
              deck: '#111A2E',
              plate: '#17223B',
              manifest: '#3DD9C2',
              flag: '#F5A623',
              hazard: '#E5544D',
            }
          }
        }
      }
    </script>
    <style>
        body { background-color: #0B1220; }
        .ledger-row:hover { background-color: #17223B; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #22304d; border-radius: 4px; }
    </style>
</head>
<body class="font-sans text-slate-200 antialiased">
    <div class="min-h-screen flex flex-col">
        <header class="border-b border-white/5 bg-deck/60 backdrop-blur">
            <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
                <a href="{{ route('dashboard.index') }}" class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded bg-manifest/10 border border-manifest/30 flex items-center justify-center text-manifest font-mono font-bold text-sm">M</span>
                    <div class="leading-tight">
                        <div class="font-semibold tracking-tight text-slate-100">Manifest</div>
                        <div class="text-[11px] text-slate-500 font-mono uppercase tracking-wider">CSV &rarr; Shopify import log</div>
                    </div>
                </a>
                <nav class="flex items-center gap-1 text-sm">
                    <a href="{{ route('dashboard.index') }}" class="px-3 py-1.5 rounded-md {{ request()->routeIs('dashboard.index') ? 'bg-plate text-slate-100' : 'text-slate-400 hover:text-slate-200' }}">Uploads</a>
                    <a href="{{ route('dashboard.logs') }}" class="px-3 py-1.5 rounded-md {{ request()->routeIs('dashboard.logs') ? 'bg-plate text-slate-100' : 'text-slate-400 hover:text-slate-200' }}">Event log</a>
                    <a href="{{ route('uploads.create') }}" class="ml-2 px-3 py-1.5 rounded-md bg-manifest text-hull font-medium hover:bg-manifest/90">New upload</a>
                </nav>
            </div>
        </header>

        <main class="flex-1 max-w-6xl w-full mx-auto px-6 py-8">
            @if (session('success'))
                <div class="mb-6 rounded-md border border-manifest/30 bg-manifest/10 text-manifest px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-6 rounded-md border border-hazard/30 bg-hazard/10 text-hazard px-4 py-3 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="border-t border-white/5 py-4 text-center text-xs text-slate-600 font-mono">
            Laravel 12 &middot; Queue-driven Shopify product import
        </footer>
    </div>
</body>
</html>
