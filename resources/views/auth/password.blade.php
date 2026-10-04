<div>
    <!-- Waste no more time arguing what a good man should be, be one. - Marcus Aurelius -->
</div>
@extends('clinic.layouts.app')

@section('title', 'Perbarui kata sandi')

@section('content')
<section class="max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-card">
    <h1 class="font-heading text-xl font-bold text-slate-900">Perbarui kata sandi</h1>
    <p class="mt-2 text-sm text-slate-600">Gunakan minimal 12 karakter, huruf besar-kecil, dan angka.</p>
    <form class="mt-6 space-y-4" method="POST" action="{{ route('password.update') }}">
        @csrf
        @method('PUT')
        <label class="block text-sm font-semibold">Kata sandi saat ini<input class="mt-1 w-full rounded-xl border-slate-300" name="current_password" type="password" autocomplete="current-password" required></label>
        <label class="block text-sm font-semibold">Kata sandi baru<input class="mt-1 w-full rounded-xl border-slate-300" name="password" type="password" autocomplete="new-password" required></label>
        <label class="block text-sm font-semibold">Ulangi kata sandi baru<input class="mt-1 w-full rounded-xl border-slate-300" name="password_confirmation" type="password" autocomplete="new-password" required></label>
        <button class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white" type="submit">Simpan kata sandi</button>
    </form>
</section>
@endsection
