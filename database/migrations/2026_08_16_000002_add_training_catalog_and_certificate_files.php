<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The worker Training tab groups records by category and by whether they are
     * required for the role, and shows the certificate file issued on completion.
     * None of that was captured yet, so both tables gain additive columns only.
     */
    public function up(): void
    {
        Schema::table('training_records', function (Blueprint $table) {
            $table->string('category', 60)->nullable()->after('provider');
            $table->boolean('is_required')->default(true)->after('category');
            $table->foreignId('certification_id')->nullable()->after('worker_id')
                ->constrained()->nullOnDelete();
        });

        Schema::table('certifications', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('status');
            $table->string('file_name')->nullable()->after('file_path');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_name');
            $table->foreignId('uploaded_by')->nullable()->after('file_size')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable()->after('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::table('certifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('uploaded_by');
            $table->dropColumn(['file_path', 'file_name', 'file_size', 'uploaded_at']);
        });

        Schema::table('training_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('certification_id');
            $table->dropColumn(['category', 'is_required']);
        });
    }
};
