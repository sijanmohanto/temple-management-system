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
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_payment_id')->constrained('collection_payments')->restrictOnDelete();
            $table->enum('target_type', ['pledge', 'monthly_contribution', 'voluntary_event', 'voluntary_general'])->index();
            $table->foreignId('pledge_id')->nullable()->constrained('pledges')->restrictOnDelete();
            $table->foreignId('monthly_contribution_id')->nullable()->constrained('monthly_committee_contributions')->restrictOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('notes', 255)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['pledge_id', 'is_active']);
            $table->index(['monthly_contribution_id', 'is_active']);
            $table->index(['event_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
