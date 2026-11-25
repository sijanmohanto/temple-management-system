<?php

use App\Models\EventDocument;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_documents', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->string('report_category', 50)->nullable()->after('document_type');
            $table->unsignedSmallInteger('financial_year')->nullable()->after('report_category');

            $table->index('report_category');
            $table->index('financial_year');
        });

        // Backfill existing documents with UUIDs
        EventDocument::withTrashed()->whereNull('uuid')->each(function ($doc) {
            $doc->uuid = (string) Str::uuid();
            $doc->saveQuietly();
        });

        Schema::table('event_documents', function (Blueprint $table) {
            $table->unique('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_documents', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropIndex(['report_category']);
            $table->dropIndex(['financial_year']);
            $table->dropColumn(['uuid', 'report_category', 'financial_year']);
        });
    }
};
