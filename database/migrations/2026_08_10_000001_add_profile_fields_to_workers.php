<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->string('gender', 30)->nullable()->after('date_of_birth');
            $table->string('trade', 150)->nullable()->after('position');
            $table->date('start_date')->nullable()->after('primary_project_id');
            $table->date('end_date')->nullable()->after('start_date');
            $table->text('notes')->nullable()->after('end_date');
            $table->json('documents')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn([
                'date_of_birth',
                'gender',
                'trade',
                'start_date',
                'end_date',
                'notes',
                'documents',
            ]);
        });
    }
};
