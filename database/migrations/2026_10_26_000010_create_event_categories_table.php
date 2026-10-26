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
        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_en', 100);
            $table->string('name_bn', 100)->nullable();
            $table->string('slug', 100)->unique();
            $table->text('description_en')->nullable();
            $table->text('description_bn')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_categories');
    }
};
