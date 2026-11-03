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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number', 32)->unique();
            $table->string('complainant_name', 255);
            $table->string('complainant_phone', 50);
            $table->string('complainant_email', 150)->nullable();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('collection_payment_id')->nullable()->constrained('collection_payments')->nullOnDelete();
            $table->string('category', 50)->default('other');
            $table->string('subject', 255);
            $table->text('description');
            $table->string('attachment_path', 500)->nullable();
            $table->string('status', 30)->default('submitted')->index();
            $table->string('priority', 20)->default('medium')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_response')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'priority', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
