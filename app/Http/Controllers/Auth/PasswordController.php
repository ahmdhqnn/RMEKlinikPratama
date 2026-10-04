<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.password');
    }

    public function update(Request $request, AuthenticationService $authentication): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
        ]);

        $authentication->updatePassword($request->user(), $validated['password']);

        return back()->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
