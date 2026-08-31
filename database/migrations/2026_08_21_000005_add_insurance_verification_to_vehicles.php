<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Insurance being on file is not the same as somebody having checked it,
            // so confirmation is tracked separately from the policy dates.
            $table->string('insurance_status', 20)->default('unverified')->after('policy_end_date');
            $table->timestamp('insurance_verified_at')->nullable()->after('insurance_status');
            $table->foreignId('insurance_verified_by')
                ->nullable()
                ->after('insurance_verified_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('insurance_verification_notes')->nullable()->after('insurance_verified_by');

            $table->index(['company_id', 'insurance_status']);
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'insurance_status']);
            $table->dropConstrainedForeignId('insurance_verified_by');
            $table->dropColumn([
                'insurance_status',
                'insurance_verified_at',
                'insurance_verification_notes',
            ]);
        });
    }
};
