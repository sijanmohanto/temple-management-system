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
        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 20)->unique();
            $table->string('account_name_en', 150);
            $table->string('account_name_bn', 150)->nullable();
            $table->enum('account_type', ['asset', 'liability', 'equity', 'income', 'expense'])->index();
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->foreignId('parent_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->string('period_name', 100);
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->boolean('is_closed')->default(false)->index();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('journal_number', 32)->unique();
            $table->date('entry_date')->index();
            $table->text('description');
            $table->string('source_type', 60)->nullable()->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('posted')->index();
            $table->decimal('total_debit', 12, 2)->default(0.00);
            $table->decimal('total_credit', 12, 2)->default(0.00);
            $table->foreignId('event_id')->nullable()->constrained('events')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversed_journal_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            // Idempotency: prevent posting the same source record more than once
            $table->unique(['source_type', 'source_id'], 'uq_journal_source');
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->enum('entry_type', ['debit', 'credit']);
            $table->decimal('amount', 12, 2);
            $table->foreignId('event_id')->nullable()->constrained('events')->restrictOnDelete();
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->index(['financial_account_id', 'entry_type']);
            $table->index(['event_id']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number', 32)->unique();
            $table->date('expense_date')->index();
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->foreignId('payment_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('title_en', 200);
            $table->string('title_bn', 200)->nullable();
            $table->string('vendor_name', 150)->nullable();
            $table->string('vendor_phone', 30)->nullable();
            $table->string('invoice_number', 50)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->string('payment_method', 50)->default('cash');
            $table->text('notes')->nullable();
            $table->foreignId('event_id')->nullable()->constrained('events')->restrictOnDelete();
            $table->string('status', 32)->default('pending_approval')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fund_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 32)->unique();
            $table->date('transfer_date')->index();
            $table->foreignId('from_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->foreignId('to_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('fee_amount', 12, 2)->default(0.00);
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->string('status', 32)->default('posted')->index();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('manual_incomes', function (Blueprint $table) {
            $table->id();
            $table->string('income_number', 32)->unique();
            $table->date('income_date')->index();
            $table->foreignId('deposit_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->foreignId('income_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('title_en', 200)->nullable();
            $table->string('title_bn', 200)->nullable();
            $table->string('payer_name', 150)->nullable();
            $table->string('payment_method', 50)->default('cash');
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('event_id')->nullable()->constrained('events')->restrictOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->string('status', 32)->default('posted')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_incomes');
        Schema::dropIfExists('fund_transfers');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounting_periods');
        Schema::dropIfExists('financial_accounts');
    }
};
