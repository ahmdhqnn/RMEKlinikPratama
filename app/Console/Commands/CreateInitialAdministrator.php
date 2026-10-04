<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('clinic:create-admin')]
#[Description('Create the first administrator when the user database is empty.')]
class CreateInitialAdministrator extends Command
{
    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->components->error('Administrator awal hanya dapat dibuat sebelum ada akun pengguna.');

            return self::FAILURE;
        }

        $name = $this->ask('Nama administrator');
        $email = $this->ask('Email administrator');
        $password = $this->secret('Kata sandi (minimal 12 karakter, huruf besar/kecil dan angka)');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Administrator->value,
            'is_active' => true,
        ]);

        $this->components->info('Administrator pertama berhasil dibuat. Masuk melalui halaman login aplikasi.');

        return self::SUCCESS;
    }
}
