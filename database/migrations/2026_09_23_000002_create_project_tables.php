<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Progress Project: projects, activities, task board (with template source),
 * evidence, campaign progress log and content production log.
 */
return new class extends Migration {
    public function up(): void
    {
        // ─── Project master ───
        Schema::create('projects', function (Blueprint $table): void {
            $table->string('project_id')->primary();
            $table->string('project_name');
            $table->string('client_id');
            $table->string('deal_id')->nullable();
            $table->jsonb('service');                       // selected service lines
            $table->date('start_date')->nullable();
            $table->date('deadline')->nullable();
            $table->string('overall_status')->default('Planning')->index();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->string('main_pic_employee_id')->nullable();
            $table->text('description')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestampsTz();
            $table->foreign('client_id')->references('client_id')->on('clients');
            $table->foreign('deal_id')->references('deal_id')->on('deals')->nullOnDelete();
            $table->foreign('main_pic_employee_id')->references('employee_id')->on('employees')->nullOnDelete();
        });

        // ─── Activity ───
        Schema::create('activities', function (Blueprint $table): void {
            $table->string('activity_id')->primary();
            $table->string('project_id');
            $table->string('service_id');
            $table->string('activity_name');
            $table->string('owner_employee_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('deadline')->nullable();
            $table->string('status')->default('Planning')->index();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->text('notes')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();
            $table->foreign('project_id')->references('project_id')->on('projects')->cascadeOnDelete();
            $table->foreign('service_id')->references('service_id')->on('services');
            $table->foreign('owner_employee_id')->references('employee_id')->on('employees')->nullOnDelete();
        });

        // ─── Task board ───
        Schema::create('tasks', function (Blueprint $table): void {
            $table->string('task_id')->primary();
            $table->string('project_id');
            $table->string('activity_id')->nullable();
            $table->string('task_name');
            $table->text('brief')->nullable();
            $table->string('service_id')->nullable();
            $table->string('stage')->nullable();
            $table->string('pic_employee_id')->nullable()->index();
            $table->date('start_date')->nullable();
            $table->date('deadline')->nullable()->index();
            $table->string('status')->default('To Do')->index();  // To Do|In Progress|Review|Revision|Approved|Done|Blocked
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->string('approval_status')->nullable();        // Pending|Approved|Rejected|Revision
            $table->unsignedInteger('revision_count')->default(0);
            $table->text('material_attachment')->nullable();
            $table->text('publishing_information')->nullable();
            $table->text('evidence')->nullable();
            $table->text('issue')->nullable();
            $table->text('next_action')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->boolean('is_completed')->default(false)->index();
            $table->string('source')->default('manual');          // template | manual
            $table->unsignedSmallInteger('template_sequence')->nullable();
            $table->string('created_by')->nullable();
            $table->timestampsTz();
            $table->foreign('project_id')->references('project_id')->on('projects')->cascadeOnDelete();
            $table->foreign('activity_id')->references('activity_id')->on('activities')->nullOnDelete();
            $table->foreign('service_id')->references('service_id')->on('services')->nullOnDelete();
            $table->foreign('pic_employee_id')->references('employee_id')->on('employees')->nullOnDelete();
        });

        // ─── Evidence ───
        Schema::create('evidence', function (Blueprint $table): void {
            $table->string('evidence_id')->primary();
            $table->string('task_id')->nullable();
            $table->string('activity_id')->nullable();
            $table->text('photo_link_screenshot');
            $table->string('uploaded_by_employee_id')->nullable();
            $table->timestampTz('timestamp')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->foreign('task_id')->references('task_id')->on('tasks')->cascadeOnDelete();
            $table->foreign('activity_id')->references('activity_id')->on('activities')->cascadeOnDelete();
            $table->foreign('uploaded_by_employee_id')->references('employee_id')->on('employees')->nullOnDelete();
        });

        // ─── Campaign progress log ───
        Schema::create('campaign_progress_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('project_id');
            $table->date('date');
            $table->string('metric');
            $table->decimal('value', 18, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestampsTz();
            $table->foreign('project_id')->references('project_id')->on('projects')->cascadeOnDelete();
        });

        // ─── Content production log ───
        Schema::create('content_production_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('project_id');
            $table->string('content_type');
            $table->string('production_status')->default('Draft');
            $table->text('file_or_preview')->nullable();
            $table->string('client_approval')->nullable();  // Pending|Approved|Revision|Rejected
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestampsTz();
            $table->foreign('project_id')->references('project_id')->on('projects')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_production_logs');
        Schema::dropIfExists('campaign_progress_logs');
        Schema::dropIfExists('evidence');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('projects');
    }
};