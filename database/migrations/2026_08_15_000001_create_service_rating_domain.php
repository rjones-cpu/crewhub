<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CH-11 Service Rating domain.
 *
 * Adapted from the package REFERENCE_SCHEMA: this app scopes by company_id
 * (no tenant_id) and uses major_projects rather than a generic projects table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_rating_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('policy_code');
            $table->string('name');
            $table->string('status')->default('working_draft')->index();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'major_project_id', 'policy_code'], 'uq_sr_policy_code');
            $table->index(['company_id', 'major_project_id', 'status'], 'idx_sr_policy_scope');
        });

        Schema::create('service_rating_policy_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_rating_policy_id')->constrained()->cascadeOnDelete();
            $table->string('version');
            $table->string('status')->default('working_draft')->index();
            $table->dateTime('effective_from')->nullable();
            $table->dateTime('effective_to')->nullable();
            $table->string('time_zone')->default('Australia/Perth');
            $table->json('policy_json');
            $table->string('policy_hash', 64);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['service_rating_policy_id', 'version'], 'uq_sr_policy_version');
            $table->index(
                ['service_rating_policy_id', 'status', 'effective_from', 'effective_to'],
                'idx_sr_policy_effective'
            );
        });

        Schema::table('service_rating_policies', function (Blueprint $table) {
            $table->foreign('current_version_id', 'fk_sr_policy_current_ver')
                ->references('id')
                ->on('service_rating_policy_versions')
                ->nullOnDelete();
        });

        Schema::create('company_project_service_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_project_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('current_published_snapshot_id')->nullable();
            $table->string('status')->default('active')->index();
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'major_project_id'], 'uq_cpr_company_project');
            $table->index(['company_id', 'status'], 'idx_cpr_company');
            $table->index(['major_project_id', 'status'], 'idx_cpr_project');
        });

        Schema::create('service_rating_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('company_project_service_rating_id');
            $table->unsignedBigInteger('policy_version_id');
            $table->unsignedInteger('sequence_no');
            $table->dateTime('evaluation_window_start');
            $table->dateTime('evaluation_window_end');
            $table->dateTime('evidence_cutoff_at');
            $table->char('overall_grade', 1)->nullable();
            $table->string('calculation_status');
            $table->string('publication_status');
            $table->string('data_quality_status');
            $table->string('evidence_fingerprint', 64)->nullable();
            $table->string('calculation_key', 64);
            $table->json('calculation_trace');
            $table->unsignedBigInteger('prior_snapshot_id')->nullable();
            $table->unsignedBigInteger('superseded_by_snapshot_id')->nullable();
            $table->string('calculated_by_type')->default('system');
            $table->string('calculated_by_id')->nullable();
            $table->dateTime('calculated_at');
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('published_at')->nullable();
            $table->string('correlation_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique('calculation_key', 'uq_sr_calculation_key');
            $table->unique(
                ['company_project_service_rating_id', 'sequence_no'],
                'uq_sr_snapshot_sequence'
            );
            $table->index(
                ['company_project_service_rating_id', 'publication_status', 'calculated_at'],
                'idx_sr_snapshot_current'
            );

            $table->foreign('company_project_service_rating_id', 'fk_sr_snap_cpr')
                ->references('id')
                ->on('company_project_service_ratings')
                ->restrictOnDelete();
            $table->foreign('policy_version_id', 'fk_sr_snap_policy_ver')
                ->references('id')
                ->on('service_rating_policy_versions')
                ->restrictOnDelete();
            $table->foreign('prior_snapshot_id', 'fk_sr_snap_prior')
                ->references('id')
                ->on('service_rating_snapshots')
                ->nullOnDelete();
            $table->foreign('superseded_by_snapshot_id', 'fk_sr_snap_superseded')
                ->references('id')
                ->on('service_rating_snapshots')
                ->nullOnDelete();
        });

        Schema::table('company_project_service_ratings', function (Blueprint $table) {
            $table->foreign('current_published_snapshot_id', 'fk_cpr_current_snap')
                ->references('id')
                ->on('service_rating_snapshots')
                ->nullOnDelete();
        });

        Schema::create('service_rating_criterion_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')
                ->constrained('service_rating_snapshots')
                ->cascadeOnDelete();
            $table->string('criterion_code');
            $table->boolean('applicable')->default(true);
            $table->string('applicability_reason_code')->nullable();
            $table->char('grade', 1)->nullable();
            $table->decimal('numerator', 18, 6)->nullable();
            $table->decimal('denominator', 18, 6)->nullable();
            $table->decimal('measured_value', 18, 6)->nullable();
            $table->string('measured_unit')->nullable();
            $table->string('threshold_code')->nullable();
            $table->json('threshold_json')->nullable();
            $table->string('driver_summary', 1000)->nullable();
            $table->json('result_trace');
            $table->string('data_quality_status');
            $table->unsignedInteger('exception_count')->default(0);
            $table->boolean('critical_override_applied')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['snapshot_id', 'criterion_code'], 'uq_sr_snap_criterion');
            $table->index(['criterion_code', 'grade'], 'idx_sr_criterion_grade');
        });

        Schema::create('service_rating_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_project_id')->constrained()->restrictOnDelete();
            $table->string('criterion_code');
            $table->string('exception_type');
            $table->string('reason_code');
            $table->text('reason_text');
            $table->json('scope_json');
            $table->json('evidence_json')->nullable();
            $table->dateTime('effective_from');
            $table->dateTime('effective_to');
            $table->string('status')->default('pending')->index();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();

            $table->index(
                ['company_id', 'major_project_id', 'criterion_code', 'status'],
                'idx_exception_scope'
            );
            $table->index(['effective_from', 'effective_to'], 'idx_exception_effective');
        });

        Schema::create('service_rating_critical_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_project_id')->constrained()->restrictOnDelete();
            $table->string('criterion_code');
            $table->string('critical_rule_code');
            $table->string('status')->default('pending')->index();
            $table->json('scope_json');
            $table->json('evidence_json');
            $table->text('containment_action')->nullable();
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('second_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->text('resolution_reason')->nullable();
            $table->timestamps();

            $table->index(
                ['company_id', 'major_project_id', 'status'],
                'idx_critical_override_scope'
            );
            $table->index(['effective_from', 'effective_to'], 'idx_critical_override_effective');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_rating_critical_overrides');
        Schema::dropIfExists('service_rating_exceptions');
        Schema::dropIfExists('service_rating_criterion_results');
        Schema::table('company_project_service_ratings', function (Blueprint $table) {
            $table->dropForeign(['current_published_snapshot_id']);
        });
        Schema::dropIfExists('service_rating_snapshots');
        Schema::dropIfExists('company_project_service_ratings');
        Schema::table('service_rating_policies', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });
        Schema::dropIfExists('service_rating_policy_versions');
        Schema::dropIfExists('service_rating_policies');
    }
};
