<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Managers a Crew Hub reports to for a given major project. Hard deletes on
        // purpose so a removed manager can be re-linked without hitting the unique key.
        Schema::create('project_manager_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('relationship')->default('connected')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'major_project_id', 'user_id'], 'pml_company_project_user_unique');
        });

        Schema::create('responsibility_delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_manager_link_id')->nullable()->constrained()->nullOnDelete();
            $table->string('area');
            $table->string('status')->default('not_delegated')->index();
            $table->boolean('is_delegable')->default(false);
            $table->timestamps();
            $table->unique(['company_id', 'major_project_id', 'area'], 'rd_company_project_area_unique');
        });

        Schema::create('assignment_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('action');
            $table->string('details')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_activities');
        Schema::dropIfExists('responsibility_delegations');
        Schema::dropIfExists('project_manager_links');
    }
};
