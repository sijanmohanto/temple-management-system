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
        if (Schema::hasTable('pledges') && ! Schema::hasColumn('pledges', 'visibility')) {
            Schema::table('pledges', function (Blueprint $table) {
                $table->string('visibility', 20)->default('public')->after('notes')->index();
            });
        }

        if (Schema::hasTable('people') && ! Schema::hasColumn('people', 'visibility')) {
            Schema::table('people', function (Blueprint $table) {
                $table->string('visibility', 20)->default('public')->after('status')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pledges') && Schema::hasColumn('pledges', 'visibility')) {
            Schema::table('pledges', function (Blueprint $table) {
                $table->dropColumn('visibility');
            });
        }

        if (Schema::hasTable('people') && Schema::hasColumn('people', 'visibility')) {
            Schema::table('people', function (Blueprint $table) {
                $table->dropColumn('visibility');
            });
        }
    }
};
