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
        Schema::create('complaint_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('complaints')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('activity_type', 50)->index();
            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();
            $table->text('content')->nullable();
            $table->string('visibility', 20)->default('donor')->index();
            $table->timestamps();

            $table->index(['complaint_id', 'visibility']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaint_activities');
    }
};
