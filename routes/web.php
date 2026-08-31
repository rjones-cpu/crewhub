<?php

use App\Http\Controllers\AccommodationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HierarchyController;
use App\Http\Controllers\HierarchyDelegationController;
use App\Http\Controllers\HierarchyManagerController;
use App\Http\Controllers\HierarchyWorkforceController;
use App\Http\Controllers\JourneyController;
use App\Http\Controllers\JourneyHubController;
use App\Http\Controllers\JourneyQuestionController;
use App\Http\Controllers\JourneyRiskController;
use App\Http\Controllers\MajorProjectController;
use App\Http\Controllers\ModuleActivationRequestController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReadinessCheckController;
use App\Http\Controllers\ReadinessController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\TimesheetReportController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleInsuranceController;
use App\Http\Controllers\WorkerActivityController;
use App\Http\Controllers\WorkerCertificateController;
use App\Http\Controllers\WorkerController;
use App\Http\Controllers\WorkerToolAccessController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::post('/major-projects/invitations/{invitation}/accept', [MajorProjectController::class, 'acceptInvitation'])->name('major-projects.invitations.accept');
    Route::post('/major-projects/invitations/{invitation}/decline', [MajorProjectController::class, 'declineInvitation'])->name('major-projects.invitations.decline');
    Route::post('/major-projects/{major_project}/invitations', [MajorProjectController::class, 'storeInvitations'])->name('major-projects.invitations.store');
    Route::get('/major-projects-join', [MajorProjectController::class, 'join'])->name('major-projects.join');
    Route::post('/major-projects-selection/clear', [MajorProjectController::class, 'clearSelection'])->name('major-projects.clear');
    Route::resource('major-projects', MajorProjectController::class);
    Route::post('/major-projects/{major_project}/switch', [MajorProjectController::class, 'switch'])->name('major-projects.switch');

    Route::resource('workers', WorkerController::class);
    Route::patch('/workers/{worker}/tools', [WorkerToolAccessController::class, 'update'])->name('workers.tools.update');
    Route::patch('/workers/features/{feature}', [WorkerToolAccessController::class, 'updateCompany'])
        ->whereIn('feature', ['schedule', 'timesheet', 'lms', 'journey'])
        ->name('workers.features.update');
    Route::get('/workers/{worker}/activity', [WorkerActivityController::class, 'index'])->name('workers.activity');
    Route::post('/workers/{worker}/certificates', [WorkerCertificateController::class, 'store'])->name('workers.certificates.store');
    Route::delete('/workers/{worker}/certificates/{certification}', [WorkerCertificateController::class, 'destroy'])->name('workers.certificates.destroy');

    Route::get('/hierarchy', HierarchyController::class)->name('hierarchy.index');
    Route::post('/hierarchy/managers', [HierarchyManagerController::class, 'store'])->name('hierarchy.managers.store');
    Route::delete('/hierarchy/managers/{link}', [HierarchyManagerController::class, 'destroy'])->name('hierarchy.managers.destroy');
    Route::patch('/hierarchy/delegations', [HierarchyDelegationController::class, 'update'])->name('hierarchy.delegations.update');
    Route::get('/hierarchy/workforce', HierarchyWorkforceController::class)->name('hierarchy.workforce');

    Route::get('/readiness', [ReadinessController::class, 'index'])->name('readiness.index');
    Route::post('/readiness/run-check', [ReadinessCheckController::class, 'store'])->name('readiness.run-check');

    Route::get('/journeys/export', [JourneyController::class, 'export'])->name('journeys.export');
    Route::patch('/journeys/{journey}/status', [JourneyController::class, 'updateStatus'])->name('journeys.status');
    Route::get('/journeys/vehicles', [VehicleController::class, 'index'])->name('journeys.vehicles');
    Route::get('/journeys/vehicles/create', [VehicleController::class, 'create'])->name('journeys.vehicles.create');
    Route::post('/journeys/vehicles', [VehicleController::class, 'store'])->name('journeys.vehicles.store');

    Route::get('/journeys/questions', [JourneyQuestionController::class, 'index'])->name('journeys.questions');
    Route::post('/journeys/questions', [JourneyQuestionController::class, 'store'])->name('journeys.questions.store');
    Route::put('/journeys/questions/{question}', [JourneyQuestionController::class, 'update'])->name('journeys.questions.update');
    Route::delete('/journeys/questions/{question}', [JourneyQuestionController::class, 'destroy'])->name('journeys.questions.destroy');
    Route::post('/journeys/questions/reorder', [JourneyQuestionController::class, 'reorder'])->name('journeys.questions.reorder');

    Route::get('/journeys/risk', [JourneyRiskController::class, 'index'])->name('journeys.risk');
    Route::post('/journeys/risk', [JourneyRiskController::class, 'store'])->name('journeys.risk.store');
    Route::post('/journeys/risk/{assessment}/recalculate', [JourneyRiskController::class, 'recalculate'])->name('journeys.risk.recalculate');
    Route::get('/journeys/risk/export', [JourneyRiskController::class, 'export'])->name('journeys.risk.export');
    Route::get('/journeys/hubs', [JourneyHubController::class, 'index'])->name('journeys.hubs');
    Route::post('/journeys/hubs', [JourneyHubController::class, 'store'])->name('journeys.hubs.store');
    Route::put('/journeys/hubs/{hub}', [JourneyHubController::class, 'update'])->name('journeys.hubs.update');
    Route::delete('/journeys/hubs/{hub}', [JourneyHubController::class, 'destroy'])->name('journeys.hubs.destroy');
    Route::post('/journeys/hubs/{hub}/designate', [JourneyHubController::class, 'designate'])->name('journeys.hubs.designate');

    Route::get('/journeys/insurance', [VehicleInsuranceController::class, 'index'])->name('journeys.insurance');
    Route::post('/journeys/insurance/{vehicle}', [VehicleInsuranceController::class, 'confirm'])->name('journeys.insurance.confirm');
    Route::resource('journeys', JourneyController::class)->only(['index', 'show', 'store']);
    Route::resource('accommodations', AccommodationController::class)->only(['index', 'show']);

    // Registered before the timesheets resource so they are not captured by /timesheets/{timesheet}.
    Route::get('/timesheets/reports/export', [TimesheetReportController::class, 'export'])->name('timesheets.reports.export');
    Route::get('/timesheets/reports', TimesheetReportController::class)->name('timesheets.reports');
    Route::get('/timesheets/entry', [TimesheetController::class, 'entry'])->name('timesheets.entry');
    Route::get('/timesheets/approval', [TimesheetController::class, 'approval'])->name('timesheets.approval');

    Route::resource('timesheets', TimesheetController::class)->only(['index', 'show', 'store', 'update']);
    Route::post('/timesheets/{timesheet}/submit', [TimesheetController::class, 'submit'])->name('timesheets.submit');
    Route::post('/timesheets/{timesheet}/approve-manager', [TimesheetController::class, 'approveManager'])->name('timesheets.approve-manager');
    Route::post('/timesheets/{timesheet}/approve-client', [TimesheetController::class, 'approveClient'])->name('timesheets.approve-client');
    Route::post('/timesheets/{timesheet}/return', [TimesheetController::class, 'returnTimesheet'])->name('timesheets.return');
    Route::post('/timesheets/{timesheet}/reject', [TimesheetController::class, 'reject'])->name('timesheets.reject');
    Route::post('/timesheets/run-check', [TimesheetController::class, 'runCheck'])->name('timesheets.run-check');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/activation-requests/{activationRequest}/approve', [ModuleController::class, 'approveRequest'])->name('notifications.activation-requests.approve');
    Route::post('/notifications/activation-requests/{activationRequest}/reject', [ModuleController::class, 'rejectRequest'])->name('notifications.activation-requests.reject');
    Route::patch('/notifications/{notification}', [NotificationController::class, 'update'])->name('notifications.update');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/schedule/days', [ScheduleController::class, 'updateDays'])->name('schedule.days');
    Route::post('/schedule/paint', [ScheduleController::class, 'paintDays'])->name('schedule.paint');
    Route::post('/schedule/publish', [ScheduleController::class, 'publish'])->name('schedule.publish');
    Route::post('/schedule/reset', [ScheduleController::class, 'reset'])->name('schedule.reset');
    Route::post('/schedule/requests/{modificationRequest}/acknowledge', [ScheduleController::class, 'acknowledge'])->name('schedule.requests.acknowledge');
    Route::get('/communications', fn () => Inertia::render('Communications/Index'))->name('communications.index');
    Route::get('/lms', fn () => Inertia::render('Lms/Index'))->name('lms.index');
    Route::get('/equipment', fn () => Inertia::render('Equipment/Index'))->name('equipment.index');
    Route::get('/documents', fn () => Inertia::render('Documents/Index'))->name('documents.index');
    Route::get('/settings', [ProfileController::class, 'edit'])->name('settings.index');
    Route::get('/settings/modules', [ModuleController::class, 'index'])->name('settings.modules.index');
    Route::patch('/settings/modules/{module}/paid', [ModuleController::class, 'updatePaid'])->name('settings.modules.paid');
    Route::post('/settings/modules/{module}/grant', [ModuleController::class, 'grant'])->name('settings.modules.grant');
    Route::post('/settings/modules/{module}/revoke', [ModuleController::class, 'revoke'])->name('settings.modules.revoke');
    Route::get('/settings/positions', [PositionController::class, 'index'])->name('settings.positions.index');
    Route::get('/settings/positions/template', [PositionController::class, 'template'])->name('settings.positions.template');
    Route::post('/settings/positions', [PositionController::class, 'store'])->name('settings.positions.store');
    Route::post('/settings/positions/import', [PositionController::class, 'import'])->name('settings.positions.import');
    Route::put('/settings/positions/{position}', [PositionController::class, 'update'])->name('settings.positions.update');
    Route::delete('/settings/positions/{position}', [PositionController::class, 'destroy'])->name('settings.positions.destroy');
    Route::post('/modules/{module}/request-activation', [ModuleActivationRequestController::class, 'store'])->name('modules.request-activation');
});

require __DIR__.'/auth.php';
