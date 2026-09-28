<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Log in - {{ config('app.name', 'JESCO') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&family=dancing-script:600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-2 bg-white">

        <!-- Left brand panel -->
        <div class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-slate-950 text-white px-12 py-10">
            <!-- Ambient glow -->
            <div class="pointer-events-none absolute -top-24 -left-24 w-96 h-96 bg-jesco-600/30 rounded-full blur-3xl animate-glow-pulse"></div>
            <div class="pointer-events-none absolute bottom-0 right-0 w-[28rem] h-[28rem] bg-jesco-700/20 rounded-full blur-3xl animate-glow-pulse" style="animation-delay: 2s;"></div>
            <div class="pointer-events-none absolute inset-0 opacity-[0.04]" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 24px 24px;"></div>

            <span class="relative text-4xl" style="font-family: 'Dancing Script', cursive;">Jesco</span>

            <div class="relative space-y-8 max-w-md">
                <h1 class="text-4xl font-bold leading-tight min-h-[5.5rem]"
                    x-data="{ full: 'Run your business with total control.', typed: '' }"
                    x-init="
                        const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
                        const loop = async () => {
                            while (true) {
                                for (let i = 0; i <= full.length; i++) {
                                    typed = full.slice(0, i);
                                    await sleep(45);
                                }
                                await sleep(2200);
                                for (let i = full.length; i >= 0; i--) {
                                    typed = full.slice(0, i);
                                    await sleep(25);
                                }
                                await sleep(600);
                            }
                        };
                        setTimeout(loop, 400);
                    ">
                    <span x-text="typed"></span><span class="inline-block w-[3px] h-8 -mb-1.5 bg-jesco-500 align-middle ml-0.5 animate-blink"></span>
                </h1>
                <p class="text-slate-400 text-base leading-relaxed">
                    One dashboard for inventory, orders, customers and accounts, built to keep JESCO moving fast with zero guesswork.
                </p>

                <ul class="space-y-4 pt-2">
                    <li class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-lg bg-jesco-600/20 text-jesco-400 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5V18a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18V7.5m18 0A2.25 2.25 0 0018.75 5.25H5.25A2.25 2.25 0 003 7.5m18 0l-9 6-9-6" /></svg>
                        </span>
                        <span class="text-sm text-slate-300">Real-time inventory &amp; low-stock alerts</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-lg bg-jesco-600/20 text-jesco-400 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <span class="text-sm text-slate-300">Complete sales, purchase &amp; ledger tracking</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-lg bg-jesco-600/20 text-jesco-400 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                        </span>
                        <span class="text-sm text-slate-300">Business reports, exportable as PDF</span>
                    </li>
                </ul>
            </div>

            <p class="relative text-xs text-slate-500">&copy; {{ date('Y') }} JESCO. All rights reserved.</p>
        </div>

        <!-- Right form panel -->
        <div class="flex items-center justify-center px-6 py-12 sm:px-12">
            <div class="w-full max-w-sm animate-fade-in-up">

                <div class="lg:hidden mb-8 text-center">
                    <span class="text-4xl text-jesco-600" style="font-family: 'Dancing Script', cursive;">Jesco</span>
                </div>

                <h2 class="text-2xl font-bold text-gray-900">Welcome back</h2>
                <p class="mt-1 text-sm text-gray-500">Log in to your JESCO dashboard to continue.</p>

                <x-auth-session-status class="mt-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                            </span>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                                   class="block w-full rounded-lg border-gray-300 pl-11 py-2.5 text-sm focus:border-jesco-500 focus:ring-jesco-500 transition-colors"
                                   placeholder="you@company.com">
                        </div>
                        @error('email')
                            <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div x-data="{ show: false }">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-sm text-jesco-600 hover:text-jesco-700 hover:underline">Forgot password?</a>
                            @endif
                        </div>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                            </span>
                            <input :type="show ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password"
                                   class="block w-full rounded-lg border-gray-300 pl-11 pr-11 py-2.5 text-sm focus:border-jesco-500 focus:ring-jesco-500 transition-colors"
                                   placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600">
                                <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.774 3.162 10.066 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember me -->
                    <label for="remember_me" class="flex items-center gap-2.5 cursor-pointer select-none">
                        <input id="remember_me" type="checkbox" name="remember" class="rounded border-gray-300 text-jesco-600 focus:ring-jesco-500">
                        <span class="text-sm text-gray-600">Remember me for 30 days</span>
                    </label>

                    <button type="submit" :disabled="loading"
                            class="w-full flex items-center justify-center gap-2 rounded-lg bg-jesco-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-jesco-600/30 transition-all hover:bg-jesco-700 hover:shadow-md hover:shadow-jesco-600/40 focus:outline-none focus:ring-2 focus:ring-jesco-500 focus:ring-offset-2 disabled:opacity-70 {{ $errors->has('email') ? 'animate-shake' : '' }}">
                        <svg x-show="loading" x-cloak class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="loading ? 'Signing in...' : 'Log in'"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
