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
        Schema::create('vendor_bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number', 32)->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('budget_id')->nullable()->constrained('budgets')->nullOnDelete();

            $table->string('vendor_invoice_number', 100);
            $table->date('bill_date');
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('draft');

            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2)->default(0.00);

            $table->text('notes')->nullable();
            $table->string('document_path')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('reversal_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            // Workflow and audit
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_id', 'status']);
            $table->index(['vendor_id', 'vendor_invoice_number']);
            $table->index('bill_date');
            $table->index('due_date');
            $table->index('status');
        });

        Schema::create('vendor_bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_bill_id')->constrained('vendor_bills')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
            $table->foreignId('financial_account_id')->constrained('financial_accounts');
            $table->foreignId('budget_item_id')->nullable()->constrained('budget_items')->nullOnDelete();

            $table->string('description');
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 20)->default('pcs');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('line_total', 12, 2);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['vendor_bill_id', 'financial_account_id']);
        });

        // Seed new permissions
        $permissions = [
            [
                'slug' => 'view_vendor_bills',
                'module' => 'procurement',
                'name_en' => 'View Vendor Bills',
                'name_bn' => 'সরবরাহকারী বিল দর্শন',
                'description_en' => 'Browse vendor bills, approval states, and itemized costs',
                'description_bn' => 'সরবরাহকারী বিলের বিবরণ, অনুমোদন অবস্থা ও খরচের তালিকা দেখা',
            ],
            [
                'slug' => 'manage_vendor_bills',
                'module' => 'procurement',
                'name_en' => 'Manage Vendor Bills',
                'name_bn' => 'সরবরাহকারী বিল পরিচালনা',
                'description_en' => 'Create, edit, submit, and attach invoice evidence for vendor bills',
                'description_bn' => 'সরবরাহকারী বিল তৈরি, সম্পাদনা, প্রমাণপত্র সংযুক্তকরণ ও দাখিল করা',
            ],
            [
                'slug' => 'verify_vendor_bills',
                'module' => 'procurement',
                'name_en' => 'Verify Vendor Bills',
                'name_bn' => 'সরবরাহকারী বিল যাচাই',
                'description_en' => 'Verify submitted vendor bills against physical invoices and PO terms',
                'description_bn' => 'চালান ও ক্রয় আদেশের শর্তাবলীর সাথে দাখিলকৃত বিল যাচাই করা',
            ],
            [
                'slug' => 'approve_vendor_bills',
                'module' => 'procurement',
                'name_en' => 'Approve & Reject Vendor Bills',
                'name_bn' => 'সরবরাহকারী বিল অনুমোদন ও প্রত্যাখ্যান',
                'description_en' => 'Authorize verified vendor bills for accounts payable recognition or reject with reason',
                'description_bn' => 'প্রদেয় হিসাব হিসেবে যাচাইকৃত বিল অনুমোদন করা অথবা কারণ দর্শিয়ে বাতিল করা',
            ],
            [
                'slug' => 'cancel_vendor_bills',
                'module' => 'procurement',
                'name_en' => 'Cancel Vendor Bills',
                'name_bn' => 'সরবরাহকারী বিল বাতিল',
                'description_en' => 'Cancel eligible vendor bills and reverse associated accounting obligations',
                'description_bn' => 'যোগ্য বিল বাতিল এবং সংশ্লিষ্ট আর্থিক বাধ্যবাধকতা প্রত্যাহার করা',
            ],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['slug' => $perm['slug']], $perm);
        }

        // Assign to roles
        $billPermSlugs = array_column($permissions, 'slug');

        $templeAdmin = Role::where('slug', 'temple_admin')->first();
        if ($templeAdmin) {
            $permIds = Permission::whereIn('slug', $billPermSlugs)->pluck('id');
            $templeAdmin->permissions()->syncWithoutDetaching($permIds);
        }

        $treasurer = Role::where('slug', 'treasurer')->first();
        if ($treasurer) {
            $permIds = Permission::whereIn('slug', $billPermSlugs)->pluck('id');
            $treasurer->permissions()->syncWithoutDetaching($permIds);
        }

        $committee = Role::where('slug', 'committee_member')->first();
        if ($committee) {
            $viewPermId = Permission::where('slug', 'view_vendor_bills')->value('id');
            if ($viewPermId) {
                $committee->permissions()->syncWithoutDetaching([$viewPermId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_bill_items');
        Schema::dropIfExists('vendor_bills');

        Permission::whereIn('slug', [
            'view_vendor_bills',
            'manage_vendor_bills',
            'verify_vendor_bills',
            'approve_vendor_bills',
            'cancel_vendor_bills',
        ])->delete();
    }
};
