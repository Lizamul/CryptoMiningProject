<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['DM Sans', 'sans-serif'],
                        display: ['Space Grotesk', 'sans-serif'],
                    },
                    colors: {
                        ink: '#14213d',
                        mist: '#f4f7fb',
                        signal: '#0f9d83',
                        coral: '#ef6351',
                    },
                },
            },
        };
    </script>
    @stack('head')
</head>
<body class="min-h-screen bg-mist font-sans text-slate-700 antialiased">
    <div class="min-h-screen">
        <header class="border-b border-slate-200/80 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 lg:px-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-ink text-sm font-bold text-white shadow-lg shadow-ink/15">CM</span>
                    <span>
                        <span class="block font-display text-base font-bold tracking-tight text-ink">Crypto Mining</span>
                        <span class="block text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Market intelligence</span>
                    </span>
                </a>
                <div class="flex items-center gap-4 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                    <span class="hidden sm:inline">Data operations</span>
                    <span class="h-2 w-2 rounded-full bg-signal shadow-[0_0_0_4px_rgba(15,157,131,0.12)]"></span>
                    <span class="text-signal">Live</span>
                    <span class="hidden h-5 w-px bg-slate-200 sm:block"></span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-[11px] font-bold tracking-[0.14em] text-slate-500 transition hover:text-coral">Logout</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-5 py-8 lg:px-8 lg:py-10">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
