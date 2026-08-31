<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A Crew Hub major project now represents a Camp prime company (the rows Camp
     * shows in the coordinator "prime" table), not a Camp project record. Camp
     * project ids are no longer unique per major project, so the link table is
     * re-keyed on the Camp company id.
     */
    public function up(): void
    {
        Schema::table('camp_project_links', function (Blueprint $table) {
            $table->unsignedBigInteger('camp_company_id')->nullable()->after('camp_company_link_id');
            $table->string('camp_company_name')->nullable()->after('camp_company_id');
            $table->string('hierarchy', 32)->nullable()->after('camp_company_name');
            $table->unsignedBigInteger('parent_camp_company_id')->nullable()->after('hierarchy');
        });

        $indexes = collect(Schema::getIndexes('camp_project_links'))->pluck('name')->all();

        Schema::table('camp_project_links', function (Blueprint $table) use ($indexes) {
            if (in_array('camp_project_links_camp_project_id_unique', $indexes, true)) {
                $table->dropUnique('camp_project_links_camp_project_id_unique');
            }

            $table->unsignedBigInteger('camp_project_id')->nullable()->change();
            $table->string('camp_project_name')->nullable()->change();

            $table->unique('camp_company_id');
            $table->index('camp_project_id');
            $table->index('parent_camp_company_id');
        });

        Schema::table('workers', function (Blueprint $table) {
            // Camp subcontractors do not become major projects; they are recorded here
            // so a worker still shows who actually employs them.
            $table->string('employer_name')->nullable()->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn('employer_name');
        });

        Schema::table('camp_project_links', function (Blueprint $table) {
            $table->dropUnique(['camp_company_id']);
            $table->dropIndex(['camp_project_id']);
            $table->dropIndex(['parent_camp_company_id']);
            $table->dropColumn([
                'camp_company_id',
                'camp_company_name',
                'hierarchy',
                'parent_camp_company_id',
            ]);
        });
    }
};
