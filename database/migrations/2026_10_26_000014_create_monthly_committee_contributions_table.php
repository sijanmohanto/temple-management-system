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
        Schema::create('monthly_committee_contributions', function (Blueprint $table) {
            $table->id();
            $table->string('contribution_code', 32)->unique();
            $table->foreignId('committee_membership_id')->constrained('committee_memberships')->restrictOnDelete();
            $table->date('contribution_month')->index(); // First day of the month: YYYY-MM-01
            $table->decimal('expected_amount', 12, 2);
            $table->enum('status', ['unpaid', 'partial', 'paid', 'waived'])->default('unpaid')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('waived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('waived_at')->nullable();
            $table->text('waiver_reason')->nullable();
            $table->timestamps();

            // Unique membership and month
            $table->unique(['committee_membership_id', 'contribution_month'], 'uniq_membership_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_committee_contributions');
    }
};
