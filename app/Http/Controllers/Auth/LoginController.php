<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuthenticationService $authenticationService): RedirectResponse
    {
        $authenticationService->login($request->validated());

        $destination = match (true) {
            $request->user()->hasRole(UserRole::Administrator) => route('admin.index'),
            $request->user()->hasRole(UserRole::Pharmacy) => route('farmasi.index'),
            $request->user()->hasRole(UserRole::ClinicManager) => route('manajemen.index'),
            $request->user()->hasRole(UserRole::MedicalRecordsOfficer) => route('quality-reviews.index'),
            default => route('dashboard'),
        };

        return redirect()->to($destination);
    }

    public function destroy(AuthenticationService $authenticationService): RedirectResponse
    {
        $authenticationService->logout();

        return redirect()->route('login')->with('success', 'Anda telah keluar dengan aman.');
    }
}
