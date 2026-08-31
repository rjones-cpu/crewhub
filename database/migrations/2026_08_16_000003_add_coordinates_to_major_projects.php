<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store the map pin for a major project address so the location can be
     * re-rendered later without reverse-geocoding every time.
     */
    public function up(): void
    {
        Schema::table('major_projects', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('major_projects', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
