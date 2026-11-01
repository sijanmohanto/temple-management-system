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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_code', 32)->unique();
            $table->string('name', 200);
            $table->string('contact_person', 150)->nullable();
            $table->string('phone', 30)->nullable()->index();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('vendor_type', 60)->nullable()->index();
            $table->string('tax_id', 60)->nullable()->index();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        if (Schema::hasTable('expenses') && ! Schema::hasColumn('expenses', 'vendor_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->foreignId('vendor_id')->nullable()->after('expense_date')->constrained('vendors')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'vendor_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('vendor_id');
            });
        }

        Schema::dropIfExists('vendors');
    }
};
