<?php

use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\LegacyController;
use App\Http\Controllers\Api\V1\MeetingController;
use App\Http\Controllers\Api\V1\PersonalTaskController;
use App\Http\Controllers\Api\V1\ScheduleConflictController;
use App\Http\Controllers\Api\V1\SessionController;
use App\Http\Controllers\Api\V1\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/session', [SessionController::class, 'session'])->name('session');
Route::post('/login', [SessionController::class, 'login'])->middleware('throttle:login')->name('login');

Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [EventController::class, 'show'])->whereNumber('event')->name('events.show');

Route::middleware('auth:web')->group(function () {
    Route::get('/me', [SessionController::class, 'me'])->name('me');
    Route::post('/logout', [SessionController::class, 'logout'])->name('logout');

    Route::get('/my/events', [WorkspaceController::class, 'index'])->name('my.events.index');
    Route::get('/my/events/{event}', [WorkspaceController::class, 'show'])->whereNumber('event')->name('my.events.show');
    Route::get('/my/events/{event}/conflicts', ScheduleConflictController::class)
        ->whereNumber('event')->name('my.events.conflicts');
    Route::get('/my/events/{event}/legacy', [LegacyController::class, 'index'])->whereNumber('event')->name('my.events.legacy.index');
    Route::get('/my/events/{event}/legacy/{lesson}', [LegacyController::class, 'show'])
        ->whereNumber(['event', 'lesson'])->name('my.events.legacy.show');

    Route::get('/my/events/{event}/meetings', [MeetingController::class, 'index'])
        ->whereNumber('event')->name('my.events.meetings.index');
    Route::scopeBindings()->group(function () {
        Route::get('/my/events/{event}/meetings/{meeting}', [MeetingController::class, 'show'])
            ->whereNumber(['event', 'meeting'])->name('my.events.meetings.show');
        Route::get('/my/events/{event}/meetings/{meeting}/tasks/{task}', [MeetingController::class, 'task'])
            ->whereNumber(['event', 'meeting', 'task'])->name('my.events.meetings.task');
    });

    Route::get('/my/tasks', [PersonalTaskController::class, 'index'])->name('my.tasks.index');
    Route::get('/my/tasks/{task}', [PersonalTaskController::class, 'show'])->whereNumber('task')->name('my.tasks.show');
    Route::patch('/my/tasks/{task}/status', [PersonalTaskController::class, 'updateStatus'])->whereNumber('task')->name('my.tasks.status');
});
