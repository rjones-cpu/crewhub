<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('type', 30);
            $table->string('question');
            $table->string('description')->nullable();
            $table->string('help_text')->nullable();
            $table->json('options')->nullable();
            $table->unsignedSmallInteger('max_characters')->nullable();

            // Links an answer back to a risk factor so the engine can score it.
            $table->string('risk_key', 40)->nullable();
            $table->unsignedTinyInteger('risk_weight')->default(0);

            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_questions');
    }
};
