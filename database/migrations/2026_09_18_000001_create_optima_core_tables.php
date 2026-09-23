<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->uuid('parent_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_profiles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('auth_user_id')->unique();
            $table->uuid('department_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('role')->default('crm_staff')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });

        Schema::create('leads', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('organization')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('normalized_email')->nullable()->index();
            $table->string('normalized_phone')->nullable()->index();
            $table->string('source');
            $table->string('segment')->nullable();
            $table->string('status')->default('new')->index();
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('next_follow_up_at')->nullable()->index();
            $table->uuid('owner_id')->nullable()->index();
            $table->uuid('created_by')->nullable();
            $table->uuid('converted_client_id')->nullable();
            $table->timestampTz('archived_at')->nullable()->index();
            $table->timestampsTz();
            $table->foreign('owner_id')->references('id')->on('user_profiles')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('user_profiles')->nullOnDelete();
        });

        Schema::create('clients', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('display_name');
            $table->string('legal_name')->nullable();
            $table->string('sector')->nullable();
            $table->string('status')->default('active')->index();
            $table->uuid('owner_id')->nullable();
            $table->uuid('converted_from_lead_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();
            $table->foreign('owner_id')->references('id')->on('user_profiles')->nullOnDelete();
            $table->foreign('converted_from_lead_id')->references('id')->on('leads')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('user_profiles')->nullOnDelete();
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->foreign('converted_client_id')->references('id')->on('clients')->nullOnDelete();
        });

        Schema::create('interactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('lead_id')->index();
            $table->string('channel')->index();
            $table->text('summary');
            $table->string('outcome')->nullable();
            $table->timestampTz('occurred_at')->index();
            $table->uuid('created_by')->nullable();
            $table->timestampsTz();
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('user_profiles')->nullOnDelete();
        });

        Schema::create('follow_ups', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('lead_id')->index();
            $table->uuid('assignee_id')->nullable()->index();
            $table->string('title');
            $table->timestampTz('due_at')->index();
            $table->string('priority')->default('normal');
            $table->string('status')->default('open')->index();
            $table->timestampTz('completed_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestampsTz();
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('assignee_id')->references('id')->on('user_profiles')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('user_profiles')->nullOnDelete();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('actor_id')->nullable()->index();
            $table->string('event')->index();
            $table->string('auditable_type');
            $table->uuid('auditable_id')->nullable();
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX leads_search_idx ON leads USING gin (to_tsvector(\'simple\', coalesce(name, \'\') || \' \' || coalesce(organization, \'\')))');
        }
    }

    public function down(): void
    {
        Schema::table('leads', fn (Blueprint $table) => $table->dropForeign(['converted_client_id']));
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('follow_ups');
        Schema::dropIfExists('interactions');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('user_profiles');
        Schema::dropIfExists('departments');
    }
};
