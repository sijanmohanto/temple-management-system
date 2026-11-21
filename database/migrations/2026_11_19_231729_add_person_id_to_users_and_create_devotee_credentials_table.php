<?php

use App\Models\Permission;
use App\Models\Role;
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
        // 1. Extend users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('person_id')
                ->nullable()
                ->after('id')
                ->constrained('people')
                ->nullOnDelete();

            $table->string('email')->nullable()->change();
        });

        // 2. Create devotee_credentials table
        Schema::create('devotee_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('encrypted_password');
            $table->timestamps();
        });

        // 3. Create email_otps table
        Schema::create('email_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('email');
            $table->string('otp_hash');
            $table->string('purpose', 50);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'purpose', 'expires_at'], 'user_purpose_expires_idx');
        });

        // 4. Seed dedicated devotee credential permissions
        $permissions = [
            [
                'slug' => 'view_devotee_passwords',
                'module' => 'people',
                'name_en' => 'View Devotee Passwords',
                'name_bn' => 'ভক্তের পাসওয়ার্ড দর্শন',
                'description_en' => 'Decrypt and reveal devotee account passwords in protected admin views',
                'description_bn' => 'সুরক্ষিত অ্যাডমিন প্যানেলে ভক্তের অ্যাকাউন্টের পাসওয়ার্ড ডিক্রিপ্ট করে দেখা',
            ],
            [
                'slug' => 'update_devotee_passwords',
                'module' => 'people',
                'name_en' => 'Update Devotee Passwords',
                'name_bn' => 'ভক্তের পাসওয়ার্ড পরিবর্তন',
                'description_en' => 'Reset and update devotee login passwords and credentials',
                'description_bn' => 'ভক্তের লগইন পাসওয়ার্ড ও প্রমাণপত্র রিসেট ও হালনাগাদ করা',
            ],
        ];

        foreach ($permissions as $permData) {
            $permission = Permission::firstOrCreate(['slug' => $permData['slug']], $permData);

            $superAdmin = Role::where('slug', 'super_admin')->first();
            if ($superAdmin && ! $superAdmin->permissions()->where('permissions.id', $permission->id)->exists()) {
                $superAdmin->permissions()->attach($permission->id);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('slug', ['view_devotee_passwords', 'update_devotee_passwords'])->delete();

        Schema::dropIfExists('email_otps');
        Schema::dropIfExists('devotee_credentials');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropColumn('person_id');
            $table->string('email')->nullable(false)->change();
        });
    }
};
