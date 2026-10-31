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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 32)->unique();
            $table->string('title', 200);
            $table->date('request_date')->index();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('budget_id')->nullable()->constrained('budgets')->nullOnDelete();
            $table->string('status', 32)->default('draft')->index();
            $table->decimal('total_estimated_amount', 12, 2)->default(0.00);
            $table->text('justification')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_converted_to_po')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->string('description', 255);
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 30)->default('pcs');
            $table->decimal('estimated_unit_price', 12, 2);
            $table->decimal('estimated_total', 12, 2);
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->foreignId('budget_item_id')->nullable()->constrained('budget_items')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('purchase_requests');
    }
};
