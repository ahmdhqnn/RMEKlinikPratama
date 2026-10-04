<div>
    <!-- Do what you can, with what you have, where you are. - Theodore Roosevelt -->
</div>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk — Klinik Pratama</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 flex items-center justify-center p-4">
    <main class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl border border-slate-200">
        <div class="mb-8 text-center">
            <img src="{{ asset('assets/images/logo_klinik_pratama.png') }}" alt="Klinik Pratama" class="mx-auto mb-5 h-14 w-auto">
            <h1 class="text-2xl font-bold">Masuk ke Klinik Pratama</h1>
            <p class="mt-2 text-sm text-slate-600">Gunakan akun yang dibuat oleh administrator klinik.</p>
        </div>

        @if (session('success'))
            <div role="status" class="mb-5 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf
            <div>
                <label for="email" class="mb-1 block text-sm font-semibold">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20">
                @error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-semibold">Kata sandi</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20">
                @error('password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
                Tetap masuk di perangkat ini
            </label>
            <button type="submit" class="w-full rounded-xl bg-teal-800 px-4 py-3 font-bold text-white hover:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2">Masuk</button>
        </form>
    </main>
</body>
</html>
