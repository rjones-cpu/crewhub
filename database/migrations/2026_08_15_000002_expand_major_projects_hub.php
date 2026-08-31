<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('major_projects', function (Blueprint $table) {
            $table->string('address')->nullable()->after('location');
            $table->string('po_number', 100)->nullable()->after('code');
            $table->string('project_number', 100)->nullable()->after('po_number');
            $table->foreignId('manager_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->text('comments')->nullable()->after('description');
            $table->json('modules')->nullable()->after('icon');
        });

        Schema::create('company_project_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_project_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'major_project_id'], 'company_project_membership_unique');
        });

        Schema::create('project_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('major_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->unique(['major_project_id', 'company_id'], 'project_invitation_company_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_invitations');
        Schema::dropIfExists('company_project_memberships');

        Schema::table('major_projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_id');
            $table->dropColumn([
                'address',
                'po_number',
                'project_number',
                'comments',
                'modules',
            ]);
        });
    }
};
