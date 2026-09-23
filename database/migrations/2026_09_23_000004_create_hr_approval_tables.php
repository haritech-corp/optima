<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR (attendance, leave & quota, recruitment, workload snapshot) and the
 * reusable approval engine plus cross-cutting audit / automation / integration
 * logs and in-app notifications.
 */
return new class extends Migration {
    public function up(): void
    {
        // ─── HR: attendance ───
        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->string('attendance_id')->primary();         // ATD-XXXX
            $table->string('employee_id');
            $table->date('date');
            $table->timestampTz('check_in')->nullable();
            $table->timestampTz('check_out')->nullable();
            $table->string('gps_location')->nullable();
            $table->string('location_label')->nullable();
            $table->string('method')->default('manual');        // manual|gps|mobile|field
            $table->boolean('radius_validation')->nullable();
            $table->decimal('distance_meters', 10, 2)->nullable();
            $table->string('status')->default('Hadir')->index(); // Hadir|Telat|Izin|Sakit|Cuti|Absent
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestampsTz();
            $table->unique(['employee_id', 'date']);
            $table->foreign('employee_id')->references('employee_id')->on('employees')->cascadeOnDelete();
        });

        // ─── HR: leave quotas ───
        Schema::create('leave_quotas', function (Blueprint $table): void {
            $table->id();
            $table->string('employee_id');
            $table->unsignedSmallInteger('year');
            $table->string('type');                             // Cuti Tahunan|Izin|Sakit
            $table->decimal('total_days', 6, 1)->default(12);
            $table->decimal('used_days', 6, 1)->default(0);
            $table->unique(['employee_id', 'year', 'type']);
            $table->timestampsTz();
            $table->foreign('employee_id')->references('employee_id')->on('employees')->cascadeOnDelete();
        });

        // ─── HR: leave / permission ───
        Schema::create('leave_requests', function (Blueprint $table): void {
            $table->string('leave_id')->primary();              // LEV-XXXX
            $table->string('employee_id');
            $table->string('type');                             // Cuti|Izin|Sakit (controlled)
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('duration_days', 5, 1)->default(1);
            $table->text('reason')->nullable();
            $table->text('evidence')->nullable();
            $table->string('approval_status')->default('Diajukan')->index(); // Diajukan|Disetujui Atasan|Disetujui HR|Selesai|Ditolak
            $table->string('approval_request_id')->nullable();
            $table->decimal('remaining_quota', 6, 1)->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestampsTz();
            $table->foreign('employee_id')->references('employee_id')->on('employees');
        });

        // ─── HR: recruitment ───
        Schema::create('recruitments', function (Blueprint $table): void {
            $table->string('recruitment_id')->primary();        // RCV-XXXX
            $table->string('position');
            $table->string('requester_department_id');
            $table->string('vacancy_status')->default('Department Request')->index();
            $table->string('candidate_name')->nullable();
            $table->string('selection_stage')->nullable();
            $table->string('recruiter_employee_id')->nullable();
            $table->date('date')->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();
            $table->foreign('requester_department_id')->references('department_id')->on('departments');
            $table->foreign('recruiter_employee_id')->references('employee_id')->on('employees')->nullOnDelete();
        });

        // ─── HR: workload snapshot (kept in sync by services) ───
        Schema::create('employee_workloads', function (Blueprint $table): void {
            $table->string('employee_id')->primary();
            $table->unsignedInteger('active_tasks')->default(0);
            $table->unsignedInteger('overdue_tasks')->default(0);
            $table->unsignedInteger('done_tasks_30d')->default(0);
            $table->unsignedTinyInteger('total_load')->default(0); // weighted 0-100
            $table->timestampTz('synced_at')->nullable();
            $table->timestampsTz();
            $table->foreign('employee_id')->references('employee_id')->on('employees')->cascadeOnDelete();
        });

        // ─── Reusable approval engine ───
        Schema::create('approval_requests', function (Blueprint $table): void {
            $table->string('request_id')->primary();            // APV-XXXX
            $table->string('record_type');                      // invoice_request|payment|reimbursement|leave_request|task|budget|client_approval|content_approval
            $table->string('record_id');
            $table->string('requester_employee_id');
            $table->string('current_approver_employee_id')->nullable();
            $table->string('status')->default('Pending')->index(); // Pending|Approved|Rejected|Revision
            $table->timestampTz('submitted_at')->useCurrent();
            $table->timestampTz('decided_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestampsTz();
            $table->index(['record_type', 'record_id']);
        });

        Schema::create('approval_steps', function (Blueprint $table): void {
            $table->id();
            $table->string('approval_request_id');
            $table->unsignedTinyInteger('sequence');
            $table->string('approver_type');                    // manager|role|finance|hr
            $table->string('approver_employee_id')->nullable();
            $table->string('decision')->nullable();             // Approve|Reject|Revision
            $table->timestampTz('timestamp')->nullable();
            $table->text('note')->nullable();
            $table->foreign('approval_request_id')->references('request_id')->on('approval_requests')->cascadeOnDelete();
        });

        // ─── In-app notifications ───
        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('employee_id')->index();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('link')->nullable();
            $table->string('type')->default('info');
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();
            $table->foreign('employee_id')->references('employee_id')->on('employees')->cascadeOnDelete();
        });

        // ─── Audit log (immutable) ───
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->bigIncrements('audit_id');
            $table->string('user_id')->nullable()->index();
            $table->timestampTz('timestamp')->useCurrent()->index();
            $table->string('record_type')->index();
            $table->string('record_id')->index();
            $table->string('field_changed');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->ipAddress('ip_address')->nullable();
        });

        // ─── Automation log (success / failure / retry) ───
        Schema::create('automation_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('automation_name');
            $table->string('record_type');
            $table->string('record_id');
            $table->timestampTz('triggered_at')->useCurrent();
            $table->string('status')->default('success');       // success|failed|retry
            $table->text('error_message')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->index(['automation_name', 'status']);
        });

        // ─── External integration runs (Zoho, Make.com) ───
        Schema::create('integration_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('integration');                      // zoho|make
            $table->string('direction');                        // out|in
            $table->string('record_type');
            $table->string('record_id');
            $table->string('external_id')->nullable()->index();
            $table->string('status')->default('success');
            $table->jsonb('request_payload')->nullable();
            $table->jsonb('response_payload')->nullable();
            $table->text('error')->nullable();
            $table->timestampTz('executed_at')->useCurrent();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_runs');
        Schema::dropIfExists('automation_logs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('approval_steps');
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('employee_workloads');
        Schema::dropIfExists('recruitments');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_quotas');
        Schema::dropIfExists('attendance_records');
    }
};