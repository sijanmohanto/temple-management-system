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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('event_code', 32)->unique();
            $table->string('name_en', 150);
            $table->string('name_bn', 150)->nullable();
            $table->foreignId('event_category_id')->constrained('event_categories')->restrictOnDelete();
            $table->unsignedSmallInteger('event_year')->index();
            $table->text('description_en')->nullable();
            $table->text('description_bn')->nullable();
            $table->string('venue', 255)->nullable();
            $table->date('start_date')->index();
            $table->date('end_date');
            $table->date('collection_start_date')->nullable()->index();
            $table->date('collection_end_date')->nullable();
            $table->enum('status', ['draft', 'upcoming', 'active', 'closing', 'closed'])->default('draft')->index();
            $table->enum('closing_mode', ['close_and_lock', 'close_with_authorized_adjustments'])->nullable();
            $table->text('closing_reason')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->text('reopen_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
