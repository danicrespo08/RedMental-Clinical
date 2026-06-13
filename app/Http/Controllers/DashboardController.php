<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Hhrr\Clinic;
use App\Models\Hhrr\Employee;
use App\Models\It\Admission  as ItAdmission;
use App\Models\It\Session    as ItSession;
use App\Models\Hhrr\Patient;
use App\Models\Psr\Admission as PsrAdmission;
use App\Models\Psr\Authorization as PsrAuthorization;
use App\Models\Psr\GroupSession;
use App\Models\Psr\ProgressNote as PsrProgressNote;
use App\Models\Psr\ServiceLog as PsrServiceLog;
use Illuminate\Support\Facades\DB;
use App\Models\Tcm\Admission as TcmAdmission;
use App\Models\Tcm\Contact   as TcmContact;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return view('dashboard.super-admin', [
                'totalClients'  => Client::count(),
                'activeClients' => Client::where('active', true)->count(),
                'recentClients' => Client::latest()->limit(5)->get(),
            ]);
        }

        return view('dashboard.index', [
            'client' => $user->client,
            'stats'  => [
                'psr_admitted' => PsrAdmission::where('status', 'admitted')->count(),
                'it_admitted'  => ItAdmission::where('status', 'admitted')->count(),
                'tcm_admitted' => TcmAdmission::where('status', 'admitted')->count(),

                'psr_sessions_this_month' => GroupSession::whereMonth('session_date', now()->month)->whereYear('session_date', now()->year)->count(),
                'it_sessions_this_month'  => ItSession::whereMonth('session_date', now()->month)->whereYear('session_date', now()->year)->count(),
                'tcm_contacts_this_month' => TcmContact::whereMonth('contact_at', now()->month)->whereYear('contact_at', now()->year)->count(),
            ],
            'recentPatients'    => Patient::latest()->limit(5)->get(),
            'patientsByClinic'  => Clinic::withCount('patients')->orderByDesc('patients_count')->get(),
        ]);
    }

    /**
     * PSR (Psychosocial Rehabilitation) module dashboard.
     *
     * Aggregates admission,
     * authorization, billing, progress-note and group-session counters for
     * the authenticated user's tenant, plus a compliance snapshot (admissions
     * missing bio assessment / treatment plan / FARS / active authorization)
     * and an at-risk list (admitted patients with risk_score >= 60).
     */
    public function psrDashboard(): View
    {
        $user   = auth()->user();
        $client = $user->client;

        $admCounts = PsrAdmission::selectRaw("
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'admitted'        THEN 1 ELSE 0 END) AS admitted,
            SUM(CASE WHEN status = 'discharged'      THEN 1 ELSE 0 END) AS discharged,
            SUM(CASE WHEN status = 'on_hold'         THEN 1 ELSE 0 END) AS hold,
            SUM(CASE WHEN status = 'pending_intake'  THEN 1 ELSE 0 END) AS pending_intake,
            SUM(CASE WHEN status = 'intake_complete' THEN 1 ELSE 0 END) AS intake_complete
        ")->first();
        $admissionStats = [
            'total'           => (int) ($admCounts->total ?? 0),
            'admitted'        => (int) ($admCounts->admitted ?? 0),
            'discharged'      => (int) ($admCounts->discharged ?? 0),
            'hold'            => (int) ($admCounts->hold ?? 0),
            'pending_intake'  => (int) ($admCounts->pending_intake ?? 0),
            'intake_complete' => (int) ($admCounts->intake_complete ?? 0),
        ];

        $authCounts = PsrAuthorization::selectRaw("
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN status IN ('pending', 'submitted') THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'denied'   THEN 1 ELSE 0 END) AS denied,
            SUM(CASE WHEN status = 'expired'  THEN 1 ELSE 0 END) AS expired
        ")->first();
        $authStats = [
            'approved' => (int) ($authCounts->approved ?? 0),
            'pending'  => (int) ($authCounts->pending ?? 0),
            'denied'   => (int) ($authCounts->denied ?? 0),
            'expired'  => (int) ($authCounts->expired ?? 0),
            'expiring_soon' => PsrAuthorization::where('status', 'approved')
                ->whereNotNull('approved_end_date')
                ->whereDate('approved_end_date', '<=', now()->addDays(30))
                ->whereDate('approved_end_date', '>=', now())
                ->count(),
        ];

        $billCounts = PsrServiceLog::selectRaw("
            SUM(CASE WHEN billing_status = 'unbilled'  THEN 1 ELSE 0 END) AS unbilled,
            SUM(CASE WHEN billing_status = 'submitted' THEN 1 ELSE 0 END) AS submitted,
            SUM(CASE WHEN billing_status = 'paid'      THEN 1 ELSE 0 END) AS paid,
            SUM(CASE WHEN billing_status = 'denied'    THEN 1 ELSE 0 END) AS denied,
            SUM(CASE WHEN billing_status = 'paid' THEN paid_amount ELSE 0 END) AS total_paid,
            COALESCE(SUM(units), 0) AS total_units
        ")->first();
        $billingStats = [
            'unbilled'    => (int) ($billCounts->unbilled ?? 0),
            'submitted'   => (int) ($billCounts->submitted ?? 0),
            'paid'        => (int) ($billCounts->paid ?? 0),
            'denied'      => (int) ($billCounts->denied ?? 0),
            'total_paid'  => (float) ($billCounts->total_paid ?? 0),
            'total_units' => (int) ($billCounts->total_units ?? 0),
        ];

        $noteCounts = PsrProgressNote::selectRaw("
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'draft'  THEN 1 ELSE 0 END) AS draft,
            SUM(CASE WHEN status = 'signed' THEN 1 ELSE 0 END) AS signed,
            SUM(CASE WHEN is_signed = 0     THEN 1 ELSE 0 END) AS unsigned
        ")->first();
        $noteStats = [
            'total'    => (int) ($noteCounts->total ?? 0),
            'draft'    => (int) ($noteCounts->draft ?? 0),
            'signed'   => (int) ($noteCounts->signed ?? 0),
            'unsigned' => (int) ($noteCounts->unsigned ?? 0),
        ];

        $sessionCounts = GroupSession::whereMonth('session_date', now()->month)
            ->whereYear('session_date', now()->year)
            ->selectRaw("COUNT(*) AS total, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed")
            ->first();
        $sessionsThisMonth = (int) ($sessionCounts->total ?? 0);
        $sessionsCompleted = (int) ($sessionCounts->completed ?? 0);

        $recentAdmissions = PsrAdmission::with(['patient', 'assignedTherapist', 'clinic'])
            ->latest('created_at')->limit(10)->get();

        $admittedQuery = fn () => PsrAdmission::where('status', 'admitted');
        $complianceStats = [
            'missing_bio'  => (clone $admittedQuery())
                ->where(fn ($q) => $q->whereDoesntHave('bioAssessment')
                                     ->orWhereHas('bioAssessment', fn ($b) => $b->where('is_signed', false)))
                ->count(),
            'missing_mtp'  => (clone $admittedQuery())
                ->where(fn ($q) => $q->whereDoesntHave('treatmentPlans')
                                     ->orWhereHas('treatmentPlans', fn ($t) => $t->where('is_signed', false)))
                ->count(),
            'missing_fars' => (clone $admittedQuery())
                ->whereDoesntHave('farsAssessments')->count(),
            'no_auth'      => (clone $admittedQuery())
                ->whereDoesntHave('authorizations', fn ($a) => $a->where('status', 'approved')
                    ->where(function ($w) {
                        $w->whereNull('approved_end_date')
                          ->orWhereDate('approved_end_date', '>=', now());
                    }))
                ->count(),
        ];

        $atRiskAdmissions = PsrAdmission::where('status', 'admitted')
            ->where('risk_score', '>=', 60)
            ->with(['patient', 'assignedTherapist', 'clinic'])
            ->orderByDesc('risk_score')
            ->limit(10)
            ->get();

        return view('clinical.psr.dashboard', compact(
            'client',
            'admissionStats', 'authStats', 'billingStats', 'noteStats',
            'sessionsThisMonth', 'sessionsCompleted',
            'recentAdmissions', 'complianceStats', 'atRiskAdmissions',
        ));
    }
}
