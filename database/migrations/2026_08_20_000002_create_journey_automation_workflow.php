<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journeys', function (Blueprint $table) {
            $table->unsignedTinyInteger('workflow_step')->default(1)->after('code');
            $table->string('workflow_state', 40)->default('detected')->after('workflow_step');
            $table->string('source_type', 40)->default('manual')->after('workflow_state');
            $table->string('source_id', 100)->nullable()->after('source_type');
            $table->json('source_payload')->nullable()->after('source_id');
            $table->string('detection_reason')->nullable()->after('source_payload');
            $table->string('shift', 80)->nullable()->after('hub');
            $table->unsignedSmallInteger('passenger_count')->default(0)->after('shift');
            $table->boolean('insurance_verified')->default(false)->after('passenger_count');
            $table->unsignedTinyInteger('risk_score')->nullable()->after('risk_level');
            $table->json('risk_factors')->nullable()->after('risk_score');
            $table->boolean('requires_approval')->default(false)->after('risk_factors');
            $table->timestamp('detected_at')->nullable()->after('requires_approval');
            $table->timestamp('escalated_at')->nullable()->after('detected_at');
            $table->timestamp('started_at')->nullable()->after('escalated_at');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->foreignId('duplicate_of_id')
                ->nullable()
                ->after('completed_at')
                ->constrained('journeys')
                ->nullOnDelete();

            $table->unique(
                ['company_id', 'source_type', 'source_id'],
                'journeys_company_source_unique'
            );
            $table->index(['workflow_state', 'workflow_step']);
        });

        Schema::create('journey_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30)->default('passenger');
            $table->string('status', 30)->default('invited');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['journey_id', 'worker_id']);
        });

        Schema::create('journey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journey_id')->constrained()->cascadeOnDelete();
            $table->string('question_key', 80);
            $table->string('question');
            $table->text('answer')->nullable();
            $table->unsignedTinyInteger('risk_points')->default(0);
            $table->timestamps();

            $table->unique(['journey_id', 'question_key']);
        });

        Schema::create('journey_risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journey_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->string('outcome', 20);
            $table->json('factors');
            $table->string('engine_version', 30)->default('rules-v1');
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('calculated_at');
            $table->timestamps();
        });

        Schema::create('journey_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journey_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('name');
            $table->string('status', 30)->default('pending');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['journey_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_checkpoints');
        Schema::dropIfExists('journey_risk_assessments');
        Schema::dropIfExists('journey_answers');
        Schema::dropIfExists('journey_participants');

        Schema::table('journeys', function (Blueprint $table) {
            $table->dropUnique('journeys_company_source_unique');
            $table->dropIndex(['workflow_state', 'workflow_step']);
            $table->dropConstrainedForeignId('duplicate_of_id');
            $table->dropColumn([
                'workflow_step',
                'workflow_state',
                'source_type',
                'source_id',
                'source_payload',
                'detection_reason',
                'shift',
                'passenger_count',
                'insurance_verified',
                'risk_score',
                'risk_factors',
                'requires_approval',
                'detected_at',
                'escalated_at',
                'started_at',
                'completed_at',
            ]);
        });
    }
};
