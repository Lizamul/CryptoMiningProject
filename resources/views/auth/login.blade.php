<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['DM Sans', 'sans-serif'], display: ['Space Grotesk', 'sans-serif'] } } } };
    </script>
</head>
<body class="min-h-screen bg-[#f4f7fb] font-sans text-slate-700 antialiased">
    <main class="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">
        <section class="relative hidden overflow-hidden bg-[#14213d] px-12 py-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -right-28 -top-28 h-80 w-80 rounded-full border border-white/10"></div>
            <div class="absolute -bottom-32 -left-20 h-96 w-96 rounded-full border border-[#0f9d83]/30"></div>
            <div class="relative">
                <div class="mb-16 flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-white text-sm font-bold text-[#14213d]">CM</span>
                    <span class="font-display text-base font-bold">Crypto Mining</span>
                </div>
                <p class="mb-4 text-xs font-bold uppercase tracking-[0.22em] text-[#6ee7c8]">Private data operations</p>
                <h1 class="max-w-lg font-display text-5xl font-bold leading-tight tracking-tight">A clearer view of every market signal.</h1>
                <p class="mt-6 max-w-md text-sm leading-7 text-slate-300">Secure access to your OHLCV collection, exchange health, and machine-learning datasets.</p>
            </div>
            <p class="relative text-xs font-medium text-slate-400">Primary time-series data platform · {{ now()->year }}</p>
        </section>

        <section class="flex items-center justify-center px-5 py-12 sm:px-10">
            <div class="w-full max-w-md">
                <div class="mb-10 lg:hidden">
                    <div class="mb-6 grid h-10 w-10 place-items-center rounded-xl bg-[#14213d] text-sm font-bold text-white">CM</div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#0f9d83]">Crypto Mining</p>
                </div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#0f9d83]">Welcome back</p>
                <h2 class="mt-3 font-display text-3xl font-bold tracking-tight text-[#14213d]">Sign in to dashboard</h2>
                <p class="mt-3 text-sm leading-6 text-slate-500">Use your administrator account to continue.</p>

                @if ($errors->any())
                    <div class="mt-6 rounded-xl border border-[#ef6351]/20 bg-[#ef6351]/10 px-4 py-3 text-sm text-[#c24131]">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                    @csrf
                    <label class="block">
                        <span class="mb-2 block text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-[#14213d] outline-none transition placeholder:text-slate-300 focus:border-[#0f9d83] focus:ring-4 focus:ring-[#0f9d83]/10" placeholder="admin@cryptomining.local">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Password</span>
                        <input type="password" name="password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-[#14213d] outline-none transition placeholder:text-slate-300 focus:border-[#0f9d83] focus:ring-4 focus:ring-[#0f9d83]/10" placeholder="Enter your password">
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-500">
                        <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-[#0f9d83] focus:ring-[#0f9d83]">
                        Remember this session
                    </label>
                    <button type="submit" class="w-full rounded-xl bg-[#14213d] px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#14213d]/20 transition hover:-translate-y-0.5 hover:bg-[#1c3158] focus:outline-none focus:ring-4 focus:ring-[#14213d]/20">Enter dashboard</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
