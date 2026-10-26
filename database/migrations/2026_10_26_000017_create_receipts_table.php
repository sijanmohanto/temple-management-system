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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 32)->unique();
            $table->foreignId('collection_payment_id')->unique()->constrained('collection_payments')->restrictOnDelete();
            $table->unsignedSmallInteger('receipt_year')->index();
            $table->timestamp('issued_at')->index();
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['valid', 'voided'])->default('valid')->index();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
