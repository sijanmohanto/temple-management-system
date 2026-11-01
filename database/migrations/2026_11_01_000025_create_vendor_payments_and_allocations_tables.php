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
        // Add paid_amount and payment_status to vendor_bills if not already present
        if (Schema::hasTable('vendor_bills')) {
            Schema::table('vendor_bills', function (Blueprint $table) {
                if (! Schema::hasColumn('vendor_bills', 'paid_amount')) {
                    $table->decimal('paid_amount', 12, 2)->default(0.00)->after('total_amount');
                }
                if (! Schema::hasColumn('vendor_bills', 'payment_status')) {
                    $table->string('payment_status', 20)->default('unpaid')->after('paid_amount');
                }
            });
        }

        // Vendor Payments Table
        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 30)->unique(); // VPY-YYYY-0001
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->foreignId('financial_account_id')->constrained('financial_accounts'); // Cash/Bank/MFS asset account

            $table->date('payment_date');
            $table->string('payment_method', 30); // cash, bank_transfer, cheque, bkash, nagad, rocket, other
            $table->decimal('amount', 12, 2); // Total payment disbursement
            $table->string('reference_number', 100)->nullable(); // Cheque #, transaction ref
            $table->string('status', 20)->default('draft'); // draft, submitted, verified, approved, posted, rejected, cancelled, reversed
            $table->text('notes')->nullable();
            $table->string('document_path')->nullable();

            // Accounting linkages
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('reversal_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            // Workflow audit trail
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('posted_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_id', 'status']);
            $table->index('payment_date');
            $table->index('payment_number');
            $table->index('status');
        });

        // Vendor Payment Allocations Table
        Schema::create('vendor_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_payment_id')->constrained('vendor_payments')->cascadeOnDelete();
            $table->foreignId('vendor_bill_id')->constrained('vendor_bills')->restrictOnDelete();
            $table->decimal('allocated_amount', 12, 2);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['vendor_payment_id', 'vendor_bill_id'], 'vpa_payment_bill_idx');
            $table->index('vendor_bill_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_payment_allocations');
        Schema::dropIfExists('vendor_payments');

        if (Schema::hasTable('vendor_bills')) {
            Schema::table('vendor_bills', function (Blueprint $table) {
                if (Schema::hasColumn('vendor_bills', 'payment_status')) {
                    $table->dropColumn('payment_status');
                }
                if (Schema::hasColumn('vendor_bills', 'paid_amount')) {
                    $table->dropColumn('paid_amount');
                }
            });
        }
    }
};
