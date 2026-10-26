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
        Schema::create('pledges', function (Blueprint $table) {
            $table->id();
            $table->string('pledge_code', 32)->unique();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->restrictOnDelete();
            $table->foreignId('family_id')->nullable()->constrained('families')->restrictOnDelete();
            $table->unsignedSmallInteger('pledge_year')->index();
            $table->decimal('pledged_amount', 12, 2);
            $table->date('pledged_on')->index();
            $table->string('purpose', 150)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['unpaid', 'partial', 'paid', 'cancelled'])->default('unpaid')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id', 'person_id']);
            $table->index(['event_id', 'family_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pledges');
    }
};
