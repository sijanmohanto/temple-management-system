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
        Schema::create('receiver_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('payment_method', 50); // bkash, nagad, rocket, bank_transfer, other
            $table->string('account_name', 255);
            $table->string('account_number', 100);
            $table->string('account_type', 50)->default('merchant'); // merchant, personal, current, savings, agent
            $table->string('bank_name', 255)->nullable();
            $table->string('branch_name', 255)->nullable();
            $table->string('routing_number', 100)->nullable();
            $table->string('swift_code', 50)->nullable();
            $table->string('qr_code_image_path', 500)->nullable();
            $table->text('instructions_en')->nullable();
            $table->text('instructions_bn')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->foreignId('financial_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['payment_method', 'is_active', 'is_verified']);
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receiver_accounts');
    }
};
