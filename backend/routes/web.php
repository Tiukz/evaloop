<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EventController;
use App\Http\Controllers\Web\OrganizationController;
use App\Http\Controllers\Web\WorkspaceController;
use App\Http\Controllers\Web\UniversityOrganizationController;
use App\Http\Controllers\Web\OrganizationMemberController;
use App\Http\Controllers\Web\EventManagementController;
use App\Http\Controllers\Web\EventSeriesController;
use App\Http\Controllers\Web\EventMemberController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\MeetingController;
use App\Http\Controllers\Web\DecisionController;
use App\Http\Controllers\Web\TaskController;
use App\Http\Controllers\Web\LegacyController;
use App\Http\Controllers\Web\WorkspaceImageController;
use App\Http\Controllers\Web\LessonAttachmentController;

Route::get('/', [EventController::class, 'index'])->name('home');
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{id}', [EventController::class, 'show'])->whereNumber('id')->name('events.show');
Route::get('/events/{id}/poster', [WorkspaceImageController::class, 'poster'])->whereNumber('id')->name('events.media.poster');
Route::get('/organizations/{organization}/logo', [WorkspaceImageController::class, 'logo'])->whereNumber('organization')->name('organizations.logo.show');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.store');
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/my/organizations', [DashboardController::class, 'organizations'])->name('my.organizations');
    Route::get('/my/events', [DashboardController::class, 'events'])->name('my.events');
    Route::get('/my/tasks', [TaskController::class, 'mine'])->name('my.tasks');
    Route::put('/organizations/{organization}/logo', [WorkspaceImageController::class, 'updateLogo'])->whereNumber('organization')->name('organizations.logo.update');

    foreach (['overview' => '', 'events' => '/events'] as $section => $suffix) {
        Route::get('/organizations/{organization}'.$suffix, [OrganizationController::class, 'show'])
            ->defaults('section', $section)->name('organizations.'.$section);
    }
    foreach (['overview' => ''] as $section => $suffix) {
        Route::get('/workspace/events/{event}'.$suffix, [WorkspaceController::class, 'show'])
            ->defaults('section', $section)->name('workspace.'.$section);
    }

    // Source belongs to a previous edition: ordinary binding plus explicit cross-edition LessonPolicy.
    Route::get('/workspace/events/{event}/legacy/sources/{sourceLesson}', [LegacyController::class, 'source'])->name('legacy.source');
    Route::get('/workspace/events/{event}/legacy/sources/{sourceLesson}/action', [LegacyController::class, 'actionForm'])->name('legacy.action.create');
    Route::post('/workspace/events/{event}/legacy/sources/{sourceLesson}/action', [LegacyController::class, 'actionStore'])->name('legacy.action.store');
    Route::get('/workspace/events/{event}/legacy/sources/{sourceLesson}/attachments/{attachment}/{disposition}', [LessonAttachmentController::class, 'show'])
        ->whereNumber(['event', 'sourceLesson', 'attachment'])->whereIn('disposition', ['preview', 'download'])->name('legacy.attachments.show');

    Route::scopeBindings()->group(function () {
        Route::get('/workspace/events/{event}/legacy', [LegacyController::class, 'index'])->name('workspace.legacy');
        Route::get('/workspace/events/{event}/evaluations/create', [LegacyController::class, 'create'])->name('evaluations.create');
        Route::post('/workspace/events/{event}/evaluations', [LegacyController::class, 'store'])->name('evaluations.store');
        Route::get('/workspace/events/{event}/evaluations/{lesson}/edit', [LegacyController::class, 'edit'])->name('evaluations.edit');
        Route::patch('/workspace/events/{event}/evaluations/{lesson}', [LegacyController::class, 'update'])->name('evaluations.update');
        Route::post('/workspace/events/{event}/evaluations/{lesson}/approve', [LegacyController::class, 'approve'])->name('evaluations.approve');
        Route::post('/workspace/events/{event}/evaluations/{lesson}/attachments', [LessonAttachmentController::class, 'store'])->name('evaluations.attachments.store');
        Route::delete('/workspace/events/{event}/evaluations/{lesson}/attachments/{attachment}', [LessonAttachmentController::class, 'destroy'])->name('evaluations.attachments.destroy');

        Route::get('/workspace/events/{event}/meetings', [MeetingController::class, 'index'])->name('workspace.meetings');
        Route::get('/workspace/events/{event}/tasks', [TaskController::class, 'index'])->name('workspace.tasks');
        Route::prefix('/workspace/events/{event}')->group(function () {
            Route::get('/meetings/create', [MeetingController::class, 'create'])->name('meetings.create');
            Route::post('/meetings', [MeetingController::class, 'store'])->name('meetings.store');
            Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])->name('meetings.show');
            Route::get('/meetings/{meeting}/edit', [MeetingController::class, 'edit'])->name('meetings.edit');
            Route::patch('/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
            Route::post('/meetings/{meeting}/decisions', [DecisionController::class, 'store'])->name('decisions.store');
            Route::get('/meetings/{meeting}/decisions/{decision}/edit', [DecisionController::class, 'edit'])->name('decisions.edit');
            Route::patch('/meetings/{meeting}/decisions/{decision}', [DecisionController::class, 'update'])->name('decisions.update');
            Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
            Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
            Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
            Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
            Route::patch('/tasks/{task}/status', [TaskController::class, 'status'])->name('tasks.status');
        });

        Route::prefix('/universities/{university}/organizations')->name('university.organizations.')->group(function () {
            Route::get('/', [UniversityOrganizationController::class, 'index'])->name('index');
            Route::get('/create', [UniversityOrganizationController::class, 'create'])->name('create');
            Route::post('/', [UniversityOrganizationController::class, 'store'])->name('store');
            Route::get('/{organization}/edit', [UniversityOrganizationController::class, 'edit'])->name('edit');
            Route::patch('/{organization}', [UniversityOrganizationController::class, 'update'])->name('update');
        });
        Route::get('/organizations/{organization}/members', [OrganizationMemberController::class, 'index'])->name('organizations.members');
        Route::get('/organizations/{organization}/members/create', [OrganizationMemberController::class, 'create'])->name('organizations.members.create');
        Route::post('/organizations/{organization}/members', [OrganizationMemberController::class, 'store'])->name('organizations.members.store');
        Route::get('/organizations/{organization}/members/{membership}/edit', [OrganizationMemberController::class, 'edit'])->name('organizations.members.edit');
        Route::patch('/organizations/{organization}/members/{membership}', [OrganizationMemberController::class, 'update'])->name('organizations.members.update');

        Route::get('/organizations/{organization}/events/create', [EventManagementController::class, 'create'])->name('organization.events.create');
        Route::post('/organizations/{organization}/events', [EventManagementController::class, 'store'])->name('organization.events.store');
        Route::get('/workspace/events/{event}/conflicts', [WorkspaceController::class, 'conflicts'])->name('workspace.conflicts');
        Route::get('/workspace/events/{event}/edit', [EventManagementController::class, 'edit'])->name('workspace.edit');
        Route::patch('/workspace/events/{event}', [EventManagementController::class, 'update'])->name('workspace.update');
        Route::get('/workspace/events/{event}/publish', [EventManagementController::class, 'publication'])->name('workspace.publish.confirm');
        Route::post('/workspace/events/{event}/publish', [EventManagementController::class, 'publish'])->name('workspace.publish');
        Route::get('/workspace/events/{event}/complete', [EventManagementController::class, 'completion'])->name('workspace.complete.confirm');
        Route::post('/workspace/events/{event}/complete', [EventManagementController::class, 'complete'])->name('workspace.complete');
        Route::get('/workspace/events/{event}/cancel', [EventManagementController::class, 'cancellation'])->name('workspace.cancel.confirm');
        Route::post('/workspace/events/{event}/cancel', [EventManagementController::class, 'cancel'])->name('workspace.cancel');

        Route::get('/organizations/{organization}/series', [EventSeriesController::class, 'index'])->name('series.index');
        Route::get('/organizations/{organization}/series/create', [EventSeriesController::class, 'create'])->name('series.create');
        Route::post('/organizations/{organization}/series', [EventSeriesController::class, 'store'])->name('series.store');
        Route::get('/organizations/{organization}/series/{eventSeries}', [EventSeriesController::class, 'show'])->name('series.show');

        Route::get('/workspace/events/{event}/members', [EventMemberController::class, 'index'])->name('workspace.members');
        Route::get('/workspace/events/{event}/members/create', [EventMemberController::class, 'create'])->name('workspace.members.create');
        Route::post('/workspace/events/{event}/members', [EventMemberController::class, 'store'])->name('workspace.members.store');
        Route::get('/workspace/events/{event}/members/{membership}/edit', [EventMemberController::class, 'edit'])->name('workspace.members.edit');
        Route::patch('/workspace/events/{event}/members/{membership}', [EventMemberController::class, 'update'])->name('workspace.members.update');
        Route::delete('/workspace/events/{event}/members/{membership}', [EventMemberController::class, 'destroy'])->name('workspace.members.destroy');
        Route::put('/workspace/events/{event}/leader', [EventMemberController::class, 'leader'])->name('workspace.leader');
    });

});
