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
        Schema::table('collection_payments', function (Blueprint $table) {
            $table->foreignId('receiver_account_id')->nullable()->after('event_id')->constrained('receiver_accounts')->nullOnDelete();
            $table->string('sender_mobile', 50)->nullable()->after('transaction_reference');
            $table->string('payment_proof_path', 500)->nullable()->after('sender_mobile');
            $table->boolean('is_public_submission')->default(false)->after('payment_proof_path')->index();
            $table->string('public_donor_name', 255)->nullable()->after('is_public_submission');
            $table->string('public_donor_phone', 50)->nullable()->after('public_donor_name');
            $table->string('public_donor_email', 150)->nullable()->after('public_donor_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_payments', function (Blueprint $table) {
            $table->dropForeign(['receiver_account_id']);
            $table->dropColumn([
                'receiver_account_id',
                'sender_mobile',
                'payment_proof_path',
                'is_public_submission',
                'public_donor_name',
                'public_donor_phone',
                'public_donor_email',
            ]);
        });
    }
};
