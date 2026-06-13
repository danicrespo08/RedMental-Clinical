<?php

namespace App\Http\Controllers\Clinical\Psr;

use App\Http\Controllers\Controller;
use App\Models\Psr\Admission;
use App\Models\Psr\Intake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * PSR Intake form controller.
 *
 * One intake exists per admission (1:1) covering demographics, consents,
 * medical snapshot, care team and safety plan. Editable until signed; on
 * sign-off the admission moves from pending_intake to intake_complete.
 */
class PsrIntakeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $admission = Admission::with('patient')->findOrFail($request->query('admission_id'));

        if ($admission->intake) {
            return redirect()->route('clinical.psr.intakes.edit', $admission->intake);
        }

        return view('clinical.psr.intakes.form', [
            'admission' => $admission,
            'intake'    => new Intake(['psr_admission_id' => $admission->id]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $intake = Intake::create($this->validated($request));

        return redirect()
            ->route('clinical.psr.intakes.edit', $intake)
            ->with('status', 'Intake saved.');
    }

    public function edit(Intake $intake): View
    {
        $intake->load(['admission.patient', 'admission.clinic']);

        return view('clinical.psr.intakes.form', [
            'admission' => $intake->admission,
            'intake'    => $intake,
        ]);
    }

    public function update(Request $request, Intake $intake): RedirectResponse
    {
        abort_if($intake->is_signed, 403, 'Signed intakes cannot be edited.');

        $intake->update($this->validated($request, $intake->id));
        return redirect()
            ->route('clinical.psr.intakes.edit', $intake)
            ->with('status', 'Intake updated.');
    }

    public function destroy(Intake $intake): RedirectResponse
    {
        abort_if($intake->is_signed, 403, 'Signed intakes cannot be deleted.');
        $admissionId = $intake->psr_admission_id;
        $intake->delete();
        return redirect()
            ->route('clinical.psr.admissions.show', $admissionId)
            ->with('status', 'Intake deleted.');
    }

    /** Sign-off action — locks the intake and advances the admission status. */
    public function sign(Intake $intake): RedirectResponse
    {
        $intake->update([
            'is_signed'    => true,
            'signed_at'    => now(),
            'completed_by' => auth()->id(),
        ]);

        if ($intake->admission->status === 'pending_intake') {
            $intake->admission->update(['status' => 'intake_complete']);
        }

        return back()->with('status', 'Intake signed.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'psr_admission_id'   => ['required', 'exists:psr_admissions,id',
                Rule::unique('psr_intakes', 'psr_admission_id')->ignore($ignoreId)],
            'race'                        => ['nullable', 'string', 'max:60'],
            'ethnicity'                   => ['nullable', 'string', 'max:60'],
            'preferred_language'          => ['nullable', 'string', 'max:60'],
            'legal_guardian_name'         => ['nullable', 'string'],
            'legal_guardian_relationship' => ['nullable', 'string', 'max:60'],
            'legal_guardian_phone'        => ['nullable', 'string'],
            'medical_history_checklist'   => ['nullable', 'string'],
            'allergies'                   => ['nullable', 'string'],
            'current_medications'         => ['nullable', 'string'],
            'pcp_name'                    => ['nullable', 'string'],
            'pcp_phone'                   => ['nullable', 'string'],
            'psychiatrist_name'           => ['nullable', 'string'],
            'psychiatrist_phone'          => ['nullable', 'string'],
            'safety_plan_details'         => ['nullable', 'string'],
            'staff_comments'              => ['nullable', 'string'],
        ]);

        foreach (['interpreter_needed', 'consent_treatment', 'consent_release_info', 'receipt_hipaa',
                  'receipt_rights', 'consent_telehealth', 'emergency_plan_ack', 'safety_contract_agreed'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }
        $data['preferred_language'] = $data['preferred_language'] ?: 'English';

        return $data;
    }
}
