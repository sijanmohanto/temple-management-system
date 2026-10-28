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
        Schema::create('event_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_number', 32)->unique();
            $table->foreignId('event_id')->unique()->constrained('events')->restrictOnDelete();
            $table->date('settlement_date')->index();
            $table->decimal('total_collections', 14, 2)->default(0.00);
            $table->decimal('total_expenses', 14, 2)->default(0.00);
            $table->decimal('surplus_amount', 14, 2)->default(0.00);
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('settled_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('destination_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->string('status', 32)->default('completed')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_settlements');
    }
};
