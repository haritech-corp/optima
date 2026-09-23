<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CRM (leads, clients, communities, KOL/KOC, media, vendors, interactions,
 * follow-ups, marketing/blasting, case study) and Sales Pipeline (deals).
 */
return new class extends Migration {
    public function up(): void
    {
        // ─── Clients master ───
        Schema::create('clients', function (Blueprint $table): void {
            $table->string('client_id')->primary();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('sector')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('owner_employee_id')->nullable();
            $table->jsonb('service_history')->nullable();
            $table->decimal('total_contract', 18, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestampTz('archived_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestampsTz();
            $table->foreign('owner_employee_id')->references('employee_id')->on('employees')->nullOnDelete();
        });

        // ─── Leads ───
        Schema::create('leads', function (Blueprint $table): void {
            $table->string('lead_id')->primary();
            $table->string('name');
            $table->string('company_or_community')->nullable();
            $table->string('sector')->nullable();
            $table->string('source');
            $table->string('pic_employee_id')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('normalized_email')->nullable()->index();
            $table->string('normalized_phone')->nullable()->index();
            $table->string('status')->default('new')->index(); // new|contacted|qualified|nurturing|converted|unqualified
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('notes')->nullable();
            $table->string('client_id')->nullable();
            $table->timestampTz('converted_at')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestampsTz();
            $table->foreign('pic_employee_id')->references('employee_id')->on('employees')->nullOnDelete();
            $table->foreign('client_id')->references('client_id')->on('clients')->nullOnDelete();
        });

        // ─── Community database ───
        Schema::create('communities', function (Blueprint $table): void {
            $table->string('community_id')->primary();
            $table->string('platform');
            $table->string('community_name');
            $table->string('segment')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('relationship_status')->default('Active')->index();
            $table->string('pic_employee_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();
            $table->foreign('pic_employee_id')->references('employee_id')->on('employees')->nullOnDelete();
        });

        // ─── KOL / KOC stakeholders ───
        Schema::create('kol_kocs', function (Blueprint $table): void {
            $table->string('kol_id')->primary();
            $table->string('name');
            $table->string('category')->nullable(); // KOL | KOC | Micro | Nano ...
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('platform')->nullable();
            $table->decimal('rate_card', 18, 2)->nullable();
            $table->text('collaboration_history')->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();
        });

        // ─── Media (outlets / partners) ───
        Schema::create('media_outlets', function (Blueprint $table): void {
            $table->string('media_id')->primary();
            $table->string('media_name');
            $table->string('category')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('platform')->nullable();
            $table->text('history')->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();
        });

        // ─── Vendors ───
        Schema::create('vendors', function (Blueprint $table): void {
            $table->string('vendor_id')->primary();
            $table->string('vendor_name');
            $table->string('category')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();
        });

        // ─── Polymorphic interactions (engagement timeline) ───
        Schema::create('interactions', function (Blueprint $table): void {
            $table->string('interaction_id')->primary();
            $table->string('entity_type')->index();   // lead|client|community|kol_koc|media|vendor|deal|project|campaign
            $table->string('entity_id')->index();
            $table->timestampTz('interaction_date')->index();
            $table->string('pic_employee_id')->nullable();
            $table->text('activity');
            $table->text('result')->nullable();
            $table->text('next_action')->nullable();
            $table->date('follow_up_date')->nullable()->index();
            $table->string('status')->default('done')->index(); // done|pending|scheduled|cancelled
            $table->string('created_by')->nullable();
            $table->timestampsTz();
            $table->foreign('pic_employee_id')->references('employee_id')->on('employees')->nullOnDelete();
        });

        // ─── Follow-up centre (linked to any entity) ───
        Schema::create('follow_ups', function (Blueprint $table): void {
            $table->string('follow_up_id')->primary();
            $table->string('entity_type')->index();
            $table->string('entity_id')->index();
            $table->string('assignee_employee_id')->nullable()->index();
            $table->string('title');
            $table->timestampTz('due_at')->index();
            $table->string('priority')->default('normal');
            $table->string('status')->default('open')->index(); // open|done|cancelled
            $table->timestampTz('completed_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestampsTz();
            $table->foreign('assignee_employee_id')->references('employee_id')->on('employees')->nullOnDelete();
        });

        // ─── Marketing / blasting campaigns ───
        Schema::create('marketing_campaigns', function (Blueprint $table): void {
            $table->string('campaign_id')->primary();
            $table->string('campaign_name');
            $table->string('target_segment')->nullable();
            $table->jsonb('channel')->nullable();       // array of channels
            $table->date('date')->nullable();
            $table->text('material')->nullable();       // link / reference
            $table->string('pic_employee_id')->nullable();
            $table->string('status')->default('Planned')->index();
            $table->text('result')->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();
            $table->foreign('pic_employee_id')->references('employee_id')->on('employees')->nullOnDelete();
        });

        // ─── Case study / portfolio ───
        Schema::create('case_studies', function (Blueprint $table): void {
            $table->string('case_study_id')->primary();
            $table->string('client_id')->nullable();
            $table->string('title');
            $table->jsonb('service')->nullable();
            $table->string('timeline')->nullable();
            $table->string('result_metric')->nullable();
            $table->text('proof_link')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->foreign('client_id')->references('client_id')->on('clients')->nullOnDelete();
        });

        // ─── Sales pipeline / deals ───
        Schema::create('deals', function (Blueprint $table): void {
            $table->string('deal_id')->primary();
            $table->string('client_id');
            $table->string('pic_employee_id');
            $table->string('stage')->default('Prospek')->index(); // Prospek|Pitching|Negosiasi|Deal|Closing
            $table->jsonb('service');
            $table->decimal('deal_value', 18, 2)->default(0);
            $table->date('target_date');
            $table->unsignedTinyInteger('probability')->nullable();
            $table->string('project_id')->nullable();
            $table->text('next_action')->nullable();
            $table->date('next_action_date')->nullable();
            $table->string('status')->default('open')->index();    // open|won|lost|on_hold
            $table->string('reason_lost')->nullable();
            $table->timestampTz('last_update')->useCurrent();
            $table->timestampTz('won_at')->nullable();
            $table->timestampTz('closed_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestampsTz();
            $table->foreign('client_id')->references('client_id')->on('clients');
            $table->foreign('pic_employee_id')->references('employee_id')->on('employees');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
        Schema::dropIfExists('case_studies');
        Schema::dropIfExists('marketing_campaigns');
        Schema::dropIfExists('follow_ups');
        Schema::dropIfExists('interactions');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('media_outlets');
        Schema::dropIfExists('kol_kocs');
        Schema::dropIfExists('communities');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('clients');
    }
};