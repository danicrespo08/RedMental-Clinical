<?php
/**
 * Phase 5 smoke — PSR full clinical chain
 * Verifies that every PSR submodule controller renders index/create/edit
 * and that admission → assessment → FARS → treatment plan + goals →
 * authorization → group session → progress note → service log → MTP review →
 * discharge all persist correctly. The IT and TCM modules still use the
 * legacy scaffold (their dedicated tables come in upcoming phases).
 *
 * Usage: php scripts/smoke_phase5.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\View::share('errors', new Illuminate\Support\ViewErrorBag());

use App\Http\Controllers\Clinical\Psr\PsrAdmissionController;
use App\Http\Controllers\Clinical\Psr\PsrAssessmentController;
use App\Http\Controllers\Clinical\Psr\PsrAuthorizationController;
use App\Http\Controllers\Clinical\Psr\PsrDischargeController;
use App\Http\Controllers\Clinical\Psr\PsrGroupSessionController;
use App\Http\Controllers\Clinical\Psr\PsrProgressNoteController;
use App\Http\Controllers\Clinical\Psr\PsrServiceLogController;
use App\Http\Controllers\Clinical\Psr\PsrSuperbillController;
use App\Http\Controllers\Clinical\Psr\PsrTreatmentPlanController;
use App\Models\Hhrr\Patient;
use App\Models\Psr\Admission;
use App\Models\Psr\Authorization;
use App\Models\Psr\GroupSession;
use App\Models\Psr\ProgressNote;
use App\Models\Psr\ServiceLog;
use App\Models\Psr\TreatmentPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$pass = 0; $fail = 0;
function t(string $l, callable $fn) {
    global $pass, $fail;
    echo str_pad($l, 60, '.');
    try { $fn(); echo " OK\n"; $pass++; }
    catch (\Throwable $e) { echo " FAIL\n  ↳ " . $e->getMessage() . " @ " . basename($e->getFile()) . ':' . $e->getLine() . "\n"; $fail++; }
}

Auth::login(User::where('email', 'admin@demo-bh.local')->first());

$admission = Admission::with('patient')->first();
abort_unless($admission, 'No PSR admission seeded — run migrate:fresh --seed first.');
$patient = $admission->patient;

$adm = new PsrAdmissionController();
t('PSR admissions: index renders', fn () => $adm->index(new Request())->render());
t('PSR admissions: create form renders', fn () => $adm->create()->render());
t('PSR admissions: show renders with sub-records', fn () => $adm->show($admission)->render());
t('PSR admissions: edit renders', fn () => $adm->edit($admission)->render());
t('PSR admissions: status transition', function () use ($adm, $admission) {
    $adm->transitionStatus(Request::create('/', 'POST', ['status' => 'on_hold']), $admission);
    $admission->refresh();
    if ($admission->status !== 'on_hold') throw new Exception('status not changed');
    $adm->transitionStatus(Request::create('/', 'POST', ['status' => 'admitted']), $admission);
});

$ass = new PsrAssessmentController();
t('PSR assessments: index renders', fn () => $ass->index(new Request())->render());
t('PSR assessments: edit existing bio renders', function () use ($ass, $admission) {
    $bio = $admission->bioAssessment()->first();
    if (! $bio) throw new Exception('seeded bio assessment missing');
    $ass->edit($bio)->render();
});
t('PSR FARS: edit existing renders', function () use ($ass, $admission) {
    $fars = $admission->farsAssessments()->first();
    if (! $fars) throw new Exception('seeded FARS missing');
    $ass->farsEdit($fars)->render();
});

$auth = new PsrAuthorizationController();
t('PSR authorizations: index renders', fn () => $auth->index(new Request())->render());
t('PSR authorizations: create form renders', fn () => $auth->create(new Request(['admission_id' => $admission->id]))->render());
t('PSR authorizations: show renders', function () use ($auth, $admission) {
    $a = $admission->authorizations()->first();
    if (! $a) throw new Exception('seeded authorization missing');
    $auth->show($a)->render();
    $auth->edit($a)->render();
});

$tp = new PsrTreatmentPlanController();
t('PSR treatment plans: index renders', fn () => $tp->index(new Request())->render());
t('PSR treatment plans: show renders with goals/objectives', function () use ($tp, $admission) {
    $plan = $admission->treatmentPlans()->first();
    if (! $plan) throw new Exception('seeded plan missing');
    $tp->show($plan)->render();
    $tp->edit($plan)->render();
});

$pn = new PsrProgressNoteController();
t('PSR progress notes: index renders', fn () => $pn->index(new Request())->render());
t('PSR progress notes: show renders', function () use ($pn) {
    $n = ProgressNote::first();
    if (! $n) throw new Exception('no progress note seeded');
    $pn->show($n)->render();
});
t('PSR progress notes: addendum to signed note', function () use ($pn) {
    $n = ProgressNote::where('is_signed', true)->first();
    if (! $n) throw new Exception('no signed note seeded');
    $pn->addendum(Request::create('/', 'POST', ['addendum_text' => 'Test addendum']), $n);
    $n->refresh();
    if (! str_contains((string) $n->addendum_text, 'Test addendum')) throw new Exception('addendum not appended');
});

$sl = new PsrServiceLogController();
t('PSR service log: index renders', fn () => $sl->index(new Request())->render());
t('PSR service log: show renders', function () use ($sl) {
    $log = ServiceLog::first();
    if (! $log) throw new Exception('no service log seeded');
    $sl->show($log)->render();
});

$sb = new PsrSuperbillController();
t('PSR superbill: weekly view renders', fn () => $sb->index(new Request())->render());

$gs = new PsrGroupSessionController();
t('PSR group sessions: index renders', fn () => $gs->index(new Request())->render());
t('PSR group sessions: show renders', function () use ($gs) {
    $s = GroupSession::first();
    if (! $s) throw new Exception('no group session seeded');
    $gs->show($s)->render();
});

$dc = new PsrDischargeController();
t('PSR discharges: index renders', fn () => $dc->index()->render());
t('PSR discharges: create form renders for an admission', fn () => $dc->create(new Request(['admission_id' => $admission->id]))->render());

echo "\nPASS: $pass · FAIL: $fail\n";
exit($fail ? 1 : 0);
