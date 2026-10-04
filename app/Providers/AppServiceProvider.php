<?php

namespace App\Providers;

use App\Models\DowntimeReconciliation;
use App\Models\MedicalAttachment;
use App\Models\MedicalLetter;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordQualityReview;
use App\Models\MedicalRecordQualityReviewFollowUp;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Queue;
use App\Policies\DowntimeReconciliationPolicy;
use App\Policies\MedicalAttachmentPolicy;
use App\Policies\MedicalLetterPolicy;
use App\Policies\MedicalRecordPolicy;
use App\Policies\MedicalRecordQualityReviewFollowUpPolicy;
use App\Policies\MedicalRecordQualityReviewPolicy;
use App\Policies\MedicinePolicy;
use App\Policies\PatientPolicy;
use App\Policies\PrescriptionPolicy;
use App\Policies\QueuePolicy;
use App\Repositories\Contracts\AdministrationRepositoryInterface;
use App\Repositories\Contracts\ClinicalRecordRepositoryInterface;
use App\Repositories\Contracts\DowntimeReconciliationRepositoryInterface;
use App\Repositories\Contracts\MedicalRecordQualityReviewFollowUpRepositoryInterface;
use App\Repositories\Contracts\MedicalRecordQualityReviewRepositoryInterface;
use App\Repositories\Contracts\MedicineInventoryRepositoryInterface;
use App\Repositories\Contracts\PatientRepositoryInterface;
use App\Repositories\Contracts\PharmacyRepositoryInterface;
use App\Repositories\Contracts\QueueRepositoryInterface;
use App\Repositories\Contracts\ReportingRepositoryInterface;
use App\Repositories\Eloquent\EloquentAdministrationRepository;
use App\Repositories\Eloquent\EloquentClinicalRecordRepository;
use App\Repositories\Eloquent\EloquentDowntimeReconciliationRepository;
use App\Repositories\Eloquent\EloquentMedicalRecordQualityReviewFollowUpRepository;
use App\Repositories\Eloquent\EloquentMedicalRecordQualityReviewRepository;
use App\Repositories\Eloquent\EloquentMedicineInventoryRepository;
use App\Repositories\Eloquent\EloquentPatientRepository;
use App\Repositories\Eloquent\EloquentPharmacyRepository;
use App\Repositories\Eloquent\EloquentQueueRepository;
use App\Repositories\Eloquent\EloquentReportingRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DowntimeReconciliationRepositoryInterface::class, EloquentDowntimeReconciliationRepository::class);
        $this->app->bind(QueueRepositoryInterface::class, EloquentQueueRepository::class);
        $this->app->bind(PatientRepositoryInterface::class, EloquentPatientRepository::class);
        $this->app->bind(ClinicalRecordRepositoryInterface::class, EloquentClinicalRecordRepository::class);
        $this->app->bind(MedicalRecordQualityReviewRepositoryInterface::class, EloquentMedicalRecordQualityReviewRepository::class);
        $this->app->bind(MedicalRecordQualityReviewFollowUpRepositoryInterface::class, EloquentMedicalRecordQualityReviewFollowUpRepository::class);
        $this->app->bind(PharmacyRepositoryInterface::class, EloquentPharmacyRepository::class);
        $this->app->bind(AdministrationRepositoryInterface::class, EloquentAdministrationRepository::class);
        $this->app->bind(MedicineInventoryRepositoryInterface::class, EloquentMedicineInventoryRepository::class);
        $this->app->bind(ReportingRepositoryInterface::class, EloquentReportingRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(DowntimeReconciliation::class, DowntimeReconciliationPolicy::class);
        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(Queue::class, QueuePolicy::class);
        Gate::policy(MedicalAttachment::class, MedicalAttachmentPolicy::class);
        Gate::policy(MedicalLetter::class, MedicalLetterPolicy::class);
        Gate::policy(MedicalRecord::class, MedicalRecordPolicy::class);
        Gate::policy(MedicalRecordQualityReview::class, MedicalRecordQualityReviewPolicy::class);
        Gate::policy(MedicalRecordQualityReviewFollowUp::class, MedicalRecordQualityReviewFollowUpPolicy::class);
        Gate::policy(Prescription::class, PrescriptionPolicy::class);
        Gate::policy(Medicine::class, MedicinePolicy::class);

        View::composer('clinic.layouts.app', static function ($view): void {
            $view->with('currentUser', auth()->user());
        });

        RateLimiter::for('login', static function (Request $request): Limit {
            $email = Str::lower($request->string('email')->toString());

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });
    }
}
