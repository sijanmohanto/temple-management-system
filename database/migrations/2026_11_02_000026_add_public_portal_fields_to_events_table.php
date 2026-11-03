<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('slug', 191)->nullable()->unique()->after('name_bn');
            $table->string('banner_image_path', 255)->nullable()->after('venue');
            $table->text('short_description_en')->nullable()->after('description_en');
            $table->text('short_description_bn')->nullable()->after('description_bn');
            $table->enum('publication_status', ['draft', 'published', 'unpublished'])->default('draft')->index()->after('status');
            $table->timestamp('published_at')->nullable()->after('publication_status');
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete()->after('published_at');
            $table->timestamp('unpublished_at')->nullable()->after('published_by');
            $table->foreignId('unpublished_by')->nullable()->constrained('users')->nullOnDelete()->after('unpublished_at');
        });

        // Generate slugs for any existing events
        $existingEvents = DB::table('events')->get();
        foreach ($existingEvents as $event) {
            $baseSlug = Str::slug($event->name_en);
            if (empty($baseSlug)) {
                $baseSlug = 'event-'.$event->id;
            }
            $slug = $baseSlug;
            $counter = 1;
            while (DB::table('events')->where('slug', $slug)->where('id', '!=', $event->id)->exists()) {
                $slug = $baseSlug.'-'.$counter++;
            }
            DB::table('events')->where('id', $event->id)->update([
                'slug' => $slug,
                'publication_status' => 'draft',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['published_by']);
            $table->dropForeign(['unpublished_by']);
            $table->dropColumn([
                'slug',
                'banner_image_path',
                'short_description_en',
                'short_description_bn',
                'publication_status',
                'published_at',
                'published_by',
                'unpublished_at',
                'unpublished_by',
            ]);
        });
    }
};
