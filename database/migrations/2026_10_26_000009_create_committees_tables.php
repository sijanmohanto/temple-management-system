<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('committees', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name_en', 150)->index();
            $table->string('name_bn', 150);
            $table->text('description_en')->nullable();
            $table->text('description_bn')->nullable();
            $table->date('term_start_date')->nullable();
            $table->date('term_end_date')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('committee_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')->constrained('committees')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('designation_en', 100);
            $table->string('designation_bn', 100)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['committee_id', 'person_id', 'status'], 'committee_person_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('committee_memberships');
        Schema::dropIfExists('committees');
    }
};
