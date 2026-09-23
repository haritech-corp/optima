<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventory & GA (assets with four groups, bookings, condition history, stock
 * opname) and Finance (invoice request, invoice, payment, reimbursement,
 * project budget).
 */
return new class extends Migration {
    public function up(): void
    {
        // ─── Assets (single register, grouped by asset_group) ───
        Schema::create('assets', function (Blueprint $table): void {
            $table->string('asset_id')->primary();
            $table->string('asset_group');                  // ooh | general | platform | vehicle
            $table->string('name')->nullable();
            $table->string('location')->nullable();
            $table->string('media_type')->nullable();       // OOH only
            $table->unsignedInteger('quantity')->default(1);
            $table->string('condition')->default('Baik')->index(); // Baik|Perlu Perbaikan|Rusak
            $table->string('status')->default('Available')->index(); // group-specific status
            $table->string('client_id')->nullable();
            $table->date('rental_start')->nullable();
            $table->date('rental_end')->nullable();
            $table->string('category')->nullable();         // general assets
            $table->string('holder_employee_id')->nullable();
            $table->date('procurement_date')->nullable();
            $table->string('platform')->nullable();         // CampusNet|DietPartner|SCo
            $table->string('content_slot')->nullable();
            $table->string('campaign_id')->nullable();
            $table->string('type')->nullable();             // vehicle type
            $table->string('pic_employee_id')->nullable();
            $table->text('related_document')->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();
            $table->foreign('client_id')->references('client_id')->on('clients')->nullOnDelete();
            $table->foreign('campaign_id')->references('campaign_id')->on('marketing_campaigns')->nullOnDelete();
            $table->foreign('holder_employee_id')->references('employee_id')->on('employees')->nullOnDelete();
            $table->foreign('pic_employee_id')->references('employee_id')->on('employees')->nullOnDelete();
        });

        // ─── Asset bookings (conflict-checked) ───
        Schema::create('asset_bookings', function (Blueprint $table): void {
            $table->string('booking_id')->primary();
            $table->string('asset_id');
            $table->string('project_id')->nullable();
            $table->string('client_id')->nullable();
            $table->string('campaign_id')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('Requested')->index(); // Requested|Approved|Booked|Cancelled|Completed
            $table->string('requested_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->foreign('asset_id')->references('asset_id')->on('assets')->cascadeOnDelete();
            $table->foreign('project_id')->references('project_id')->on('projects')->nullOnDelete();
            $table->foreign('client_id')->references('client_id')->on('clients')->nullOnDelete();
            $table->foreign('campaign_id')->references('campaign_id')->on('marketing_campaigns')->nullOnDelete();
        });

        // ─── Asset condition history ───
        Schema::create('asset_condition_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('asset_id');
            $table->string('changed_by_employee_id')->nullable();
            $table->date('change_date');
            $table->string('old_condition');
            $table->string('new_condition');
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->foreign('asset_id')->references('asset_id')->on('assets')->cascadeOnDelete();
        });

        // ─── Stock opname / physical checks ───
        Schema::create('inventory_checks', function (Blueprint $table): void {
            $table->id();
            $table->string('asset_group');
            $table->date('scheduled_date');
            $table->date('executed_date')->nullable();
            $table->string('status')->default('Scheduled'); // Scheduled|In Progress|Done
            $table->string('pic_employee_id')->nullable();
            $table->text('result')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->foreign('pic_employee_id')->references('employee_id')->on('employees')->nullOnDelete();
        });

        // ─── Finance: invoice request ───
        Schema::create('invoice_requests', function (Blueprint $table): void {
            $table->string('invoice_request_id')->primary();    // IRQ-XXXX
            $table->string('requester_employee_id');
            $table->string('project_id');
            $table->string('client_id');
            $table->decimal('nominal', 18, 2)->default(0);
            $table->text('description')->nullable();
            $table->date('date');
            $table->string('approval_status')->default('Diajukan')->index(); // Diajukan|Disetujui|Ditolak
            $table->string('approval_request_id')->nullable();
            $table->string('created_by')->nullable();
            $table->timestampsTz();
            $table->foreign('requester_employee_id')->references('employee_id')->on('employees');
            $table->foreign('project_id')->references('project_id')->on('projects');
            $table->foreign('client_id')->references('client_id')->on('clients');
        });

        // ─── Finance: invoice (Zoho synced) ───
        Schema::create('invoices', function (Blueprint $table): void {
            $table->string('invoice_id')->primary();
            $table->string('client_id');
            $table->string('project_id')->nullable();
            $table->string('invoice_request_id')->nullable();
            $table->decimal('nominal', 18, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->string('zoho_invoice_id')->nullable()->index();
            $table->text('pdf_link')->nullable();
            $table->string('status')->default('Menunggu Pelunasan')->index(); // DP Diterima|Menunggu Pelunasan|Lunas|Ditunda
            $table->timestampTz('synced_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestampsTz();
            $table->foreign('client_id')->references('client_id')->on('clients');
            $table->foreign('project_id')->references('project_id')->on('projects')->nullOnDelete();
            $table->foreign('invoice_request_id')->references('invoice_request_id')->on('invoice_requests')->nullOnDelete();
        });

        // ─── Finance: payment ───
        Schema::create('payments', function (Blueprint $table): void {
            $table->string('payment_id')->primary();
            $table->string('invoice_id')->nullable();
            $table->string('request_id')->nullable();
            $table->string('pic_employee_id')->nullable();
            $table->jsonb('approval_chain')->nullable();
            $table->string('vendor_or_recipient');
            $table->date('due_date')->nullable();
            $table->decimal('nominal', 18, 2)->default(0);
            $table->string('status')->default('Diajukan')->index(); // Diajukan|Diproses|Pending|DP|Sebagian|Lunas|Tertunda
            $table->timestampTz('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestampsTz();
            $table->foreign('invoice_id')->references('invoice_id')->on('invoices')->nullOnDelete();
        });

        // ─── Finance: reimbursement ───
        Schema::create('reimbursements', function (Blueprint $table): void {
            $table->string('reimbursement_id')->primary();
            $table->string('employee_id');
            $table->date('date');
            $table->string('category');
            $table->decimal('nominal', 18, 2)->default(0);
            $table->text('evidence');                           // required
            $table->string('approval_status')->default('Diajukan')->index(); // Diajukan|Disetujui Atasan|Diproses Finance|Dibayarkan
            $table->string('approval_request_id')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestampsTz();
            $table->foreign('employee_id')->references('employee_id')->on('employees');
        });

        // ─── Finance: project budget ───
        Schema::create('project_budgets', function (Blueprint $table): void {
            $table->string('budget_id')->primary();             // BDG-XXXX
            $table->string('project_id')->unique();
            $table->decimal('initial_budget', 18, 2)->default(0);
            $table->decimal('actual', 18, 2)->default(0);
            $table->decimal('remaining', 18, 2)->default(0);
            $table->string('updated_by')->nullable();
            $table->timestampsTz();
            $table->foreign('project_id')->references('project_id')->on('projects')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_budgets');
        Schema::dropIfExists('reimbursements');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_requests');
        Schema::dropIfExists('inventory_checks');
        Schema::dropIfExists('asset_condition_logs');
        Schema::dropIfExists('asset_bookings');
        Schema::dropIfExists('assets');
    }
};