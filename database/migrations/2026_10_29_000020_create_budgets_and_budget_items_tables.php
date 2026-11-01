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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('budget_code', 32)->unique();
            $table->string('title_en', 200);
            $table->string('title_bn', 200)->nullable();
            $table->enum('budget_type', ['annual', 'event', 'general_fund'])->index();
            $table->unsignedSmallInteger('fiscal_year')->index();
            $table->foreignId('event_id')->nullable()->constrained('events')->restrictOnDelete();
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'revised', 'cancelled'])
                ->default('draft')
                ->index();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('version')->default(1);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['budget_type', 'fiscal_year']);
            $table->index(['event_id', 'status']);
        });

        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->decimal('allocated_amount', 12, 2);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['budget_id', 'financial_account_id'], 'uq_budget_account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_items');
        Schema::dropIfExists('budgets');
    }
};
