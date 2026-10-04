<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticationService
{
    public function __construct(private AuditLogService $auditLogService) {}

    /** @param array{email: string, password: string, remember?: bool} $credentials */
    public function login(array $credentials): void
    {
        if (! Auth::attempt($credentials, $credentials['remember'] ?? false)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        $user = Auth::user();

        if (! $user instanceof User || ! $user->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Akun tidak aktif atau tidak dapat digunakan.',
            ]);
        }

        request()->session()->regenerate();
        $this->auditLogService->record('LOGIN', 'Pengguna berhasil masuk ke aplikasi klinik.', $user);
    }

    public function logout(): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $this->auditLogService->record('LOGOUT', 'Pengguna keluar dari aplikasi klinik.', $user);
        }

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    public function updatePassword(User $user, string $password): void
    {
        DB::transaction(function () use ($user, $password): void {
            $user->forceFill(['password' => Hash::make($password)])->save();
            $this->auditLogService->record('PASSWORD_CHANGED', 'Pengguna memperbarui kata sandi.', $user);
        });
    }
}
