<?php
/**
 * Smoke test for the polished IT and TCM views (admissions, sessions, contacts,
 * dashboards). Boots the framework, logs in as the demo admin, and exercises
 * every controller method that backs a polished view.
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::create('/'));

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$user = App\Models\User::where('email', 'admin@demo-bh.local')->first();
if (! $user) { fwrite(STDERR, "Seed first: php artisan migrate:fresh --seed\n"); exit(1); }
Auth::login($user);

$pass = 0; $fail = 0;
function t(string $label, callable $fn): void {
    global $pass, $fail;
    try { $fn(); echo str_pad($label, 60, '.') . " OK\n"; $pass++; }
    catch (Throwable $e) { echo str_pad($label, 60, '.') . " FAIL\n  ↳ " . $e->getMessage() . "\n"; $fail++; }
}

$itAdm = new App\Http\Controllers\Clinical\It\AdmissionController();
$itSes = new App\Http\Controllers\Clinical\It\SessionController();

t('IT dashboard renders', fn () => $itAdm->dashboard()->render());
t('IT admissions index renders', fn () => $itAdm->index(new Request())->render());
t('IT admissions create form renders', fn () => $itAdm->create()->render());
t('IT sessions index renders', fn () => $itSes->index(new Request())->render());

$itA = App\Models\It\Admission::with('patient')->first();
if ($itA) {
    t('IT admission show renders', fn () => $itAdm->show($itA)->render());
    t('IT admission edit renders', fn () => $itAdm->edit($itA)->render());
    t('IT session create renders', fn () => $itSes->create($itA)->render());

    $itS = App\Models\It\Session::where('it_admission_id', $itA->id)->first();
    if ($itS) {
        t('IT session show renders', fn () => $itSes->show($itA, $itS)->render());
        t('IT session edit renders', fn () => $itSes->edit($itA, $itS)->render());
    }
}

$itTp  = new App\Http\Controllers\Clinical\It\TreatmentPlanController();
$itAuth= new App\Http\Controllers\Clinical\It\AuthorizationController();
$itSl  = new App\Http\Controllers\Clinical\It\ServiceLogController();
$itSb  = new App\Http\Controllers\Clinical\It\SuperbillController();
$itDc  = new App\Http\Controllers\Clinical\It\DischargeController();

t('IT treatment plans index renders', fn () => $itTp->index()->render());
t('IT authorizations index renders',  fn () => $itAuth->index(new Request())->render());
t('IT service log index renders',     fn () => $itSl->index(new Request())->render());
t('IT superbill weekly grid renders', fn () => $itSb->index(new Request())->render());
t('IT discharges index renders',      fn () => $itDc->index()->render());

if ($itA) {
    t('IT treatment-plan create renders', fn () => $itTp->create(new Request(['admission_id' => $itA->id]))->render());
    t('IT authorization create renders',  fn () => $itAuth->create(new Request(['admission_id' => $itA->id]))->render());
    t('IT service-log create renders',    fn () => $itSl->create(new Request(['admission_id' => $itA->id]))->render());
    t('IT discharge create renders',      fn () => $itDc->create(new Request(['admission_id' => $itA->id]))->render());

    $tp = App\Models\It\TreatmentPlan::where('it_admission_id', $itA->id)->first();
    if ($tp) {
        t('IT treatment-plan show renders', fn () => $itTp->show($tp)->render());
        t('IT treatment-plan edit renders', fn () => $itTp->edit($tp)->render());
    }
    $au = App\Models\It\Authorization::where('it_admission_id', $itA->id)->first();
    if ($au) {
        t('IT authorization show renders', fn () => $itAuth->show($au)->render());
        t('IT authorization edit renders', fn () => $itAuth->edit($au)->render());
    }
    $sl = App\Models\It\ServiceLog::where('it_admission_id', $itA->id)->first();
    if ($sl) {
        t('IT service-log show renders', fn () => $itSl->show($sl)->render());
        t('IT service-log edit renders', fn () => $itSl->edit($sl)->render());
    }

    t('IT treatment-plan AI suggest goals (mock)', function () use ($itTp, $itA) {
        $resp = $itTp->aiSuggestGoals($itA);
        $data = json_decode($resp->getContent(), true);
        if (! isset($data['goals']) || ! is_array($data['goals'])) throw new Exception('AI returned no goals');
    });
}

$tcmAdm = new App\Http\Controllers\Clinical\Tcm\AdmissionController();
$tcmCt  = new App\Http\Controllers\Clinical\Tcm\ContactController();

t('TCM dashboard renders', fn () => $tcmAdm->dashboard()->render());
t('TCM admissions index renders', fn () => $tcmAdm->index(new Request())->render());
t('TCM admissions create form renders', fn () => $tcmAdm->create()->render());
t('TCM contacts index renders', fn () => $tcmCt->index(new Request())->render());

$tcmA = App\Models\Tcm\Admission::with('patient')->first();
if ($tcmA) {
    t('TCM admission show renders', fn () => $tcmAdm->show($tcmA)->render());
    t('TCM admission edit renders', fn () => $tcmAdm->edit($tcmA)->render());
    t('TCM contact create renders', fn () => $tcmCt->create($tcmA)->render());

    $tcmC = App\Models\Tcm\Contact::where('tcm_admission_id', $tcmA->id)->first();
    if ($tcmC) {
        t('TCM contact show renders', fn () => $tcmCt->show($tcmA, $tcmC)->render());
        t('TCM contact edit renders', fn () => $tcmCt->edit($tcmA, $tcmC)->render());
    }
}

$tcmTp   = new App\Http\Controllers\Clinical\Tcm\TreatmentPlanController();
$tcmAuth = new App\Http\Controllers\Clinical\Tcm\AuthorizationController();
$tcmSl   = new App\Http\Controllers\Clinical\Tcm\ServiceLogController();
$tcmSb   = new App\Http\Controllers\Clinical\Tcm\SuperbillController();
$tcmDc   = new App\Http\Controllers\Clinical\Tcm\DischargeController();

t('TCM treatment plans index renders', fn () => $tcmTp->index()->render());
t('TCM authorizations index renders',  fn () => $tcmAuth->index(new Request())->render());
t('TCM service log index renders',     fn () => $tcmSl->index(new Request())->render());
t('TCM superbill weekly grid renders', fn () => $tcmSb->index(new Request())->render());
t('TCM discharges index renders',      fn () => $tcmDc->index()->render());

if ($tcmA) {
    t('TCM treatment-plan create renders', fn () => $tcmTp->create(new Request(['admission_id' => $tcmA->id]))->render());
    t('TCM authorization create renders',  fn () => $tcmAuth->create(new Request(['admission_id' => $tcmA->id]))->render());
    t('TCM service-log create renders',    fn () => $tcmSl->create(new Request(['admission_id' => $tcmA->id]))->render());
    t('TCM discharge create renders',      fn () => $tcmDc->create(new Request(['admission_id' => $tcmA->id]))->render());

    $tp = App\Models\Tcm\TreatmentPlan::where('tcm_admission_id', $tcmA->id)->first();
    if ($tp) {
        t('TCM treatment-plan show renders', fn () => $tcmTp->show($tp)->render());
        t('TCM treatment-plan edit renders', fn () => $tcmTp->edit($tp)->render());
    }
    $au = App\Models\Tcm\Authorization::where('tcm_admission_id', $tcmA->id)->first();
    if ($au) {
        t('TCM authorization show renders', fn () => $tcmAuth->show($au)->render());
        t('TCM authorization edit renders', fn () => $tcmAuth->edit($au)->render());
    }
    $sl = App\Models\Tcm\ServiceLog::where('tcm_admission_id', $tcmA->id)->first();
    if ($sl) {
        t('TCM service-log show renders', fn () => $tcmSl->show($sl)->render());
        t('TCM service-log edit renders', fn () => $tcmSl->edit($sl)->render());
    }

    t('TCM treatment-plan AI suggest goals (mock)', function () use ($tcmTp, $tcmA) {
        $resp = $tcmTp->aiSuggestGoals($tcmA);
        $data = json_decode($resp->getContent(), true);
        if (! isset($data['goals']) || ! is_array($data['goals'])) throw new Exception('AI returned no goals');
    });
}

echo "\nPASS: {$pass} · FAIL: {$fail}\n";
exit($fail > 0 ? 1 : 0);
