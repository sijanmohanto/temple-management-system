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
        Schema::create('collection_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_code', 32)->unique();
            $table->foreignId('payer_person_id')->nullable()->constrained('people')->restrictOnDelete();
            $table->foreignId('payer_family_id')->nullable()->constrained('families')->restrictOnDelete();
            $table->string('payer_name_override', 150)->nullable();
            $table->string('payer_phone_override', 30)->nullable();
            $table->enum('collection_type', ['event_pledge', 'event_donation', 'monthly_contribution', 'general_donation'])->index();
            $table->foreignId('event_id')->nullable()->constrained('events')->restrictOnDelete();
            $table->date('payment_date')->index();
            $table->unsignedSmallInteger('payment_year')->index();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'bkash', 'nagad', 'rocket', 'cheque', 'other'])->index();
            $table->string('transaction_reference', 150)->nullable();
            $table->string('bank_name', 150)->nullable();
            $table->string('cheque_number', 100)->nullable();
            $table->text('payment_notes')->nullable();
            $table->enum('status', ['pending_verification', 'verified', 'rejected', 'reversed'])->default('pending_verification')->index();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->boolean('is_direct_cash_entry')->default(false);
            $table->timestamp('finance_posted_at')->nullable();
            $table->string('finance_post_reference', 100)->nullable();
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
        Schema::dropIfExists('collection_payments');
    }
};
