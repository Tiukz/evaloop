<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('code', 30)->unique();
            $table->timestamps();
        });
        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->string('name', 160);
            $table->string('code', 30);
            $table->unique(['university_id', 'code']);
            $table->unique(['id', 'university_id']);
            $table->timestamps();
        });
        Schema::create('study_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->string('name', 120);
            $table->string('code', 20);
            $table->unique(['faculty_id', 'code']);
            $table->unique(['id', 'faculty_id']);
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_program_id')->nullable()->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->string('name', 150);
            $table->string('email', 190)->unique();
            $table->string('password');
            $table->boolean('is_platform_admin')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('university_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->enum('role', ['admin', 'member'])->default('member');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->dateTime('joined_at');
            $table->dateTime('ended_at')->nullable();
            $table->unique(['university_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->timestamps();
        });
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->unsignedBigInteger('faculty_id')->nullable();
            $table->unsignedBigInteger('study_program_id')->nullable();
            $table->string('name', 160);
            // Default RESTRICT: omit explicit actions on columns used by the scope CHECK.
            $table->foreign(['faculty_id', 'university_id'], 'org_faculty_scope_fk')
                ->references(['id', 'university_id'])->on('faculties');
            $table->foreign(['study_program_id', 'faculty_id'], 'org_program_scope_fk')
                ->references(['id', 'faculty_id'])->on('study_programs');
            $table->timestamps();
        });
        Schema::create('organization_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->enum('role', ['admin', 'member'])->default('member');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->dateTime('joined_at');
            $table->dateTime('ended_at')->nullable();
            $table->unique(['organization_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->timestamps();
        });
        Schema::create('event_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->string('name', 180);
            $table->unique(['id', 'organization_id']);
            $table->timestamps();
        });
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->unsignedBigInteger('event_series_id')->nullable();
            $table->foreign(['event_series_id', 'organization_id'], 'event_series_owner_fk')
                ->references(['id', 'organization_id'])->on('event_series')->restrictOnDelete()->restrictOnUpdate();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('venue', 160)->nullable();
            $table->enum('status', ['draft', 'published', 'completed', 'cancelled'])->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->index(['status', 'starts_at', 'ends_at']);
            $table->index(['event_series_id', 'starts_at']);
            $table->timestamps();
        });
        Schema::create('event_targets', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('study_program_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->primary(['event_id', 'study_program_id']);
        });
        Schema::create('event_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->enum('role', ['coordinator', 'member'])->default('member');
            $table->unique(['event_id', 'user_id']);
            $table->timestamps();
        });
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->string('title', 180);
            $table->dateTime('scheduled_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->text('summary');
            $table->text('rationale')->nullable();
            $table->timestamps();
        });
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->text('problem');
            $table->text('recommendation');
            $table->enum('visibility', ['event', 'series'])->default('event');
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('decision_id')->nullable()->constrained()->restrictOnDelete()->restrictOnUpdate();
            $table->unsignedBigInteger('assignee_user_id')->nullable();
            $table->foreign(['event_id', 'assignee_user_id'], 'task_event_assignee_fk')
                ->references(['event_id', 'user_id'])->on('event_members')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('source_lesson_id')->nullable()->constrained('lessons')->restrictOnDelete()->restrictOnUpdate();
            $table->string('title', 200);
            $table->enum('status', ['todo', 'in_progress', 'done'])->default('todo');
            $table->dateTime('due_at')->nullable();
            $table->index(['event_id', 'status']);
            $table->timestamps();
        });

        // Production is MySQL 8.0.16+. SQLite tests cover foundations, not these CHECKs.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE organizations ADD CONSTRAINT org_scope_check CHECK (study_program_id IS NULL OR faculty_id IS NOT NULL)');
            DB::statement('ALTER TABLE university_members ADD CONSTRAINT university_member_dates_check CHECK (ended_at IS NULL OR ended_at > joined_at)');
            DB::statement('ALTER TABLE organization_members ADD CONSTRAINT organization_member_dates_check CHECK (ended_at IS NULL OR ended_at > joined_at)');
            DB::statement('ALTER TABLE events ADD CONSTRAINT event_dates_check CHECK (ends_at > starts_at)');
            DB::statement("ALTER TABLE lessons ADD CONSTRAINT lesson_approval_check CHECK (approved_at IS NULL OR visibility = 'series')");
        }
    }

    public function down(): void
    {
        foreach (['tasks', 'lessons', 'decisions', 'meetings', 'event_members', 'event_targets',
            'events', 'event_series', 'organization_members', 'organizations', 'university_members',
            'users', 'study_programs', 'faculties', 'universities'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
