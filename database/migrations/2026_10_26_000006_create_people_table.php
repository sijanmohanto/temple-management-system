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
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('person_code', 50)->unique();
            $table->string('name_en', 150)->index();
            $table->string('name_bn', 150)->nullable()->index();
            $table->string('phone', 50)->nullable()->index();
            $table->string('email', 150)->nullable()->index();
            $table->date('date_of_birth')->nullable()->index();
            $table->string('gender', 20)->nullable();
            $table->string('blood_group', 10)->nullable();
            $table->string('father_name', 150)->nullable();
            $table->string('mother_name', 150)->nullable();
            $table->string('spouse_name', 150)->nullable();
            $table->string('gotra', 100)->nullable()->index();
            $table->string('occupation', 150)->nullable();
            $table->text('present_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();
            $table->foreignId('family_id')->nullable()->constrained('families')->nullOnDelete();
            $table->string('family_relationship', 50)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->text('private_notes')->nullable();
            $table->text('public_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
