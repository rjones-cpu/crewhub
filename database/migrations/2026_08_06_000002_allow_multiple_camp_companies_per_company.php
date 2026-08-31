<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('camp_company_links'))->pluck('name');

        if (! $indexes->contains('camp_company_links_company_id_index')) {
            Schema::table('camp_company_links', function (Blueprint $table) {
                // Add a non-unique FK-supporting index before dropping the
                // unique index MySQL currently uses for the foreign key.
                $table->index('company_id');
            });
        }

        $indexes = collect(Schema::getIndexes('camp_company_links'))->pluck('name');

        if ($indexes->contains('camp_company_links_company_id_unique')) {
            Schema::table('camp_company_links', function (Blueprint $table) {
                $table->dropUnique('camp_company_links_company_id_unique');
            });
        }
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('camp_company_links'))->pluck('name');

        Schema::table('camp_company_links', function (Blueprint $table) use ($indexes) {
            if ($indexes->contains('camp_company_links_company_id_index')) {
                $table->dropIndex(['company_id']);
            }

            $table->unique('company_id');
        });
    }
};
