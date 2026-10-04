<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\IntegrationConfig;
use App\Models\Medicine;
use App\Models\User;
use App\Services\AdminService;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(Request $request, AdminService $adminService, AuditLogService $auditLogService): View
    {
        $validated = $request->validate(['tab' => ['nullable', 'in:users,medicines,icd,integrations,audit']]);
        $currentUser = $request->user();
        $tab = $validated['tab'] ?? 'users';
        $data = $adminService->overview();
        $auditLogService->record('VIEW_ADMIN', 'Membuka konsol administrasi.', $currentUser);

        return view('clinic.admin.index', array_merge($data, compact('currentUser', 'tab')));
    }

    public function storeUser(Request $request, AdminService $adminService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'specialty' => ['nullable', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:30'],
            'sip' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:25'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()],
        ]);

        $adminService->createUser($validated, $request->user());

        return redirect()->route('admin.index', ['tab' => 'users'])->with('success', 'Akun dibuat. Sampaikan kredensial awal secara aman kepada pemilik akun.');
    }

    public function updateUser(Request $request, User $user, AdminService $adminService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'specialty' => ['nullable', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:30'],
            'sip' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:25'],
            'is_active' => ['required', 'boolean'],
        ]);

        $adminService->updateUser($user, $validated, $request->user());

        return redirect()->route('admin.index', ['tab' => 'users'])->with('success', 'Hak akses pengguna berhasil diperbarui.');
    }

    public function storeMedicine(Request $request, AdminService $adminService): RedirectResponse
    {
        Gate::authorize('create', Medicine::class);
        $validated = $request->validate([
            'kode_obat' => ['required', 'string', 'max:50', 'unique:medicines,kode_obat'],
            'nama_obat' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'string', 'max:100'],
            'bentuk_sediaan' => ['required', 'string', 'max:100'],
            'satuan' => ['required', 'string', 'max:20'],
            'stok' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'stok_minimum' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'nomor_batch' => ['nullable', 'string', 'max:100'],
            'expired_date' => ['required', 'date', 'after_or_equal:today'],
            'harga_satuan' => ['required', 'numeric', 'min:0'],
            'aturan_pakai_default' => ['nullable', 'string', 'max:255'],
            'kontraindikasi_alergi' => ['nullable', 'string', 'max:500'],
        ]);

        $adminService->createMedicine(array_merge($validated, ['is_active' => true]), $request->user());

        return redirect()->route('admin.index', ['tab' => 'medicines'])->with('success', 'Obat berhasil ditambahkan ke inventori.');
    }

    public function storeDiagnosis(Request $request, AdminService $adminService): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z][0-9]{2}(?:\.[A-Z0-9]{1,4})?$/i', 'unique:icd10_codes,code'],
            'name_id' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_common' => ['nullable', 'boolean'],
        ]);

        $adminService->createDiagnosis(array_merge($validated, [
            'code' => strtoupper($validated['code']),
            'is_common' => $request->boolean('is_common'),
        ]), $request->user());

        return redirect()->route('admin.index', ['tab' => 'icd'])->with('success', 'Kode ICD-10 ditambahkan ke master klinik.');
    }

    public function updateIntegration(Request $request, IntegrationConfig $config, AdminService $adminService): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['nullable', 'string', 'max:255'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:4096'],
            'base_url' => ['nullable', 'url', 'max:2048'],
            'auth_url' => ['nullable', 'url', 'max:2048'],
            'environment' => ['required', 'in:sandbox,production'],
        ]);

        if (blank($validated['client_secret'] ?? null)) {
            unset($validated['client_secret']);
        }

        $adminService->updateIntegration($config, $validated, $request->user());

        return redirect()->route('admin.index', ['tab' => 'integrations'])->with('success', 'Konfigurasi disimpan. Integrasi tetap nonaktif sampai adapter dan uji koneksi disiapkan.');
    }

    public function storeIntegration(Request $request, AdminService $adminService): RedirectResponse
    {
        $validated = $request->validate([
            'system_code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/', 'unique:integration_configs,system_code'],
            'system_name' => ['required', 'string', 'max:255'],
            'organization_id' => ['nullable', 'string', 'max:255'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:4096'],
            'base_url' => ['nullable', 'url', 'max:2048'],
            'auth_url' => ['nullable', 'url', 'max:2048'],
            'environment' => ['required', 'in:sandbox,production'],
        ]);

        $adminService->createIntegration($validated, $request->user());

        return redirect()->route('admin.index', ['tab' => 'integrations'])->with('success', 'Konfigurasi integrasi dibuat dan tetap nonaktif.');
    }
}
