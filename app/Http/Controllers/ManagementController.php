<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\ManagementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagementController extends Controller
{
    public function index(Request $request, ManagementService $managementService, AuditLogService $auditLogService): View
    {
        $currentUser = $request->user();
        $summary = $managementService->summary();
        $auditLogService->record('VIEW_EXECUTIVE_DASHBOARD', 'Membuka laporan manajemen.', $currentUser);

        return view('clinic.manajemen', array_merge($summary, compact('currentUser')));
    }
}
