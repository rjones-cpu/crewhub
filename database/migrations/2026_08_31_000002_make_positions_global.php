<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('positions', 'company_id')) {
            return;
        }

        $keepIds = DB::table('positions')
            ->selectRaw('MIN(id) as id')
            ->groupByRaw('LOWER(name)')
            ->pluck('id');

        if ($keepIds->isNotEmpty()) {
            DB::table('positions')->whereNotIn('id', $keepIds)->delete();
        }

        Schema::table('positions', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'name']);
            $table->dropColumn('company_id');
            $table->unique('name');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('positions', 'company_id')) {
            return;
        }

        Schema::table('positions', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->unique(['company_id', 'name']);
        });
    }
};
