<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorExaminationController;
use App\Http\Controllers\DowntimeReconciliationController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\MedicalRecordQualityReviewController;
use App\Http\Controllers\MedicalRecordQualityReviewFollowUpController;
use App\Http\Controllers\MedicalRecordRevisionController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\TriageController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware('role:admin,pendaftaran,perawat,dokter,dokter_gigi,farmasi,kepala_klinik,perekam_medis')->group(function (): void {
        Route::get('/account/password', [PasswordController::class, 'edit'])->name('password.edit');
        Route::put('/account/password', [PasswordController::class, 'update'])->name('password.update');
    });

    Route::middleware('role:pendaftaran,perawat,dokter,dokter_gigi')->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.home');
        Route::get('/api/live/queues', [ApiController::class, 'getQueueUpdates'])->name('api.live.queues');
    });

    Route::middleware('role:kepala_klinik')->group(function (): void {
        Route::get('/export-report', [DashboardController::class, 'exportReport'])->name('export.report');
        Route::get('/manajemen', [ManagementController::class, 'index'])->name('manajemen.index');
    });

    Route::middleware('role:perekam_medis')->prefix('review-mutu')->group(function (): void {
        Route::get('/', [MedicalRecordQualityReviewController::class, 'index'])->name('quality-reviews.index');
        Route::post('/sampling', [MedicalRecordQualityReviewController::class, 'createSamplingRun'])->name('quality-reviews.sampling.store');
        Route::get('/{medicalRecord}', [MedicalRecordQualityReviewController::class, 'show'])->name('quality-reviews.show');
        Route::post('/{medicalRecord}', [MedicalRecordQualityReviewController::class, 'store'])->name('quality-reviews.store');
    });

    Route::middleware('role:perekam_medis')->prefix('rekonsiliasi-downtime')->group(function (): void {
        Route::get('/', [DowntimeReconciliationController::class, 'index'])->name('downtime-reconciliations.index');
        Route::post('/{downtimeReconciliation}', [DowntimeReconciliationController::class, 'update'])
            ->name('downtime-reconciliations.update');
    });

    Route::middleware('role:perekam_medis,dokter,dokter_gigi')->prefix('tindak-lanjut-mutu')->group(function (): void {
        Route::get('/', [MedicalRecordQualityReviewFollowUpController::class, 'index'])->name('quality-review-follow-ups.index');
        Route::post('/{followUp}', [MedicalRecordQualityReviewFollowUpController::class, 'update'])->name('quality-review-follow-ups.update');
    });

    Route::middleware('role:pendaftaran')->group(function (): void {
        Route::post('/antrean', [QueueController::class, 'store'])->name('antrean.store');
        Route::get('/antrean/{queue}/cetak', [QueueController::class, 'printTicket'])->name('antrean.print');
        Route::get('/pasien/{patient}/identitas', [PatientController::class, 'editIdentity'])->name('pasien.identity.edit');
        Route::patch('/pasien/{patient}/identitas', [PatientController::class, 'updateIdentity'])->name('pasien.identity.update');
        Route::get('/pasien/{patient}/kartu', [PatientController::class, 'printCard'])->name('pasien.card');
    });

    Route::middleware('role:dokter,dokter_gigi')->group(function (): void {
        Route::post('/antrean/{queue}/panggil', [QueueController::class, 'callNext'])->name('antrean.call');
        Route::get('/ruang-periksa/{queue?}', [DoctorExaminationController::class, 'index'])->name('periksa.show');
        Route::post('/ruang-periksa/{queue}/soap', [DoctorExaminationController::class, 'saveSoap'])->name('periksa.soap');
        Route::post('/ruang-periksa/{queue}/odontogram', [DoctorExaminationController::class, 'saveOdontogram'])->name('periksa.odontogram');
        Route::post('/ruang-periksa/{queue}/selesai', [DoctorExaminationController::class, 'finishSession'])->name('periksa.finish');
        Route::get('/surat/{letter}/cetak', [DoctorExaminationController::class, 'printLetter'])->name('letter.print');
    });

    Route::middleware('role:dokter,dokter_gigi')->group(function (): void {
        Route::post('/pasien/{patient}/lampiran', [DoctorExaminationController::class, 'uploadAttachment'])->name('patient.attachment');
    });

    Route::middleware('role:dokter,dokter_gigi')->get(
        '/pasien/{patient}/lampiran/{attachment}',
        [PatientController::class, 'downloadAttachment'],
    )->name('pasien.attachment.download');

    Route::middleware('role:perawat')->group(function (): void {
        Route::get('/triage', [TriageController::class, 'index'])->name('triage.index');
        Route::post('/triage/{queue}/ttv', [TriageController::class, 'storeTtv'])->name('triage.ttv');
    });

    Route::middleware('role:farmasi')->prefix('farmasi')->group(function (): void {
        Route::get('/', [PharmacyController::class, 'index'])->name('farmasi.index');
        Route::post('/{prescription}/verifikasi', [PharmacyController::class, 'verify'])->name('farmasi.verify');
        Route::post('/{prescription}/siap', [PharmacyController::class, 'markReady'])->name('farmasi.ready');
        Route::post('/{prescription}/serahkan', [PharmacyController::class, 'dispense'])->name('farmasi.dispense');
        Route::post('/obat/{medicine}/stok', [PharmacyController::class, 'receiveStock'])->name('farmasi.stock.receive');
        Route::get('/{prescription}/etiket/{item?}', [PharmacyController::class, 'printEtiket'])->name('farmasi.etiket');
    });

    Route::middleware('role:pendaftaran,perawat,dokter,dokter_gigi')->group(function (): void {
        Route::get('/pasien', [PatientController::class, 'index'])->name('pasien.index');
    });

    Route::middleware('role:dokter,dokter_gigi')->group(function (): void {
        Route::get('/pasien/{patient}', [PatientController::class, 'show'])->name('pasien.show');
        Route::post('/pasien/{patient}/rekam-medis/{medicalRecord}/downtime', [DowntimeReconciliationController::class, 'store'])
            ->scopeBindings()
            ->name('downtime-reconciliations.store');
        Route::post('/pasien/{patient}/rekam-medis/{medicalRecord}/revisi', [MedicalRecordRevisionController::class, 'store'])
            ->scopeBindings()
            ->name('pasien.medical-record.revision.store');
    });

    Route::middleware('role:admin')->prefix('admin')->group(function (): void {
        Route::get('/', [AdminController::class, 'index'])->name('admin.index');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.user.store');
        Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('admin.user.update');
        Route::post('/medicines', [AdminController::class, 'storeMedicine'])->name('admin.medicine.store');
        Route::post('/icd10', [AdminController::class, 'storeDiagnosis'])->name('admin.icd10.store');
        Route::post('/integrations/{config}', [AdminController::class, 'updateIntegration'])->name('admin.integration.update');
        Route::post('/integrations', [AdminController::class, 'storeIntegration'])->name('admin.integration.store');
    });

    Route::prefix('api')->group(function (): void {
        Route::middleware('role:pendaftaran,dokter,dokter_gigi')->get('/search/patients', [ApiController::class, 'searchPatients'])->name('api.search.patients');
        Route::middleware('role:dokter,dokter_gigi')->get('/search/icd10', [ApiController::class, 'searchIcd10'])->name('api.search.icd10');
        Route::middleware('role:dokter,dokter_gigi')->get('/search/medicines', [ApiController::class, 'searchMedicines'])->name('api.search.medicines');
    });
});
