<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            [
                'slug' => 'view_procurement_reports',
                'module' => 'procurement',
                'name_en' => 'View Procurement Reports',
                'name_bn' => 'ক্রয় সংক্রান্ত প্রতিবেদন দর্শন',
                'description_en' => 'Access procurement reports section including requisitions, orders, and outstanding vendor bills',
                'description_bn' => 'ক্রয় রিকুইজিশন, ক্রয় আদেশ ও বকেয়া বিল প্রতিবেদন দেখার সাধারণ অনুমতি',
            ],
            [
                'slug' => 'view_purchase_requisition_reports',
                'module' => 'procurement',
                'name_en' => 'View Purchase Requisition Reports',
                'name_bn' => 'ক্রয় রিকুইজিশন প্রতিবেদন দর্শন',
                'description_en' => 'Access and filter purchase requisition reports, metrics, and itemized offcanvas details',
                'description_bn' => 'ক্রয় রিকুইজিশন প্রতিবেদন, সারসংক্ষেপ ও বিস্তারিত পণ্যের তালিকা দেখা',
            ],
            [
                'slug' => 'view_purchase_order_reports',
                'module' => 'procurement',
                'name_en' => 'View Purchase Order Reports',
                'name_bn' => 'ক্রয় আদেশ প্রতিবেদন দর্শন',
                'description_en' => 'Access and filter purchase order reports, committed spend, and order details',
                'description_bn' => 'ক্রয় আদেশ প্রতিবেদন, প্রতিশ্রুতিবদ্ধ ব্যয় ও বিস্তারিত আদেশের তথ্য দেখা',
            ],
            [
                'slug' => 'view_outstanding_vendor_bill_reports',
                'module' => 'procurement',
                'name_en' => 'View Outstanding Vendor Bill Reports',
                'name_bn' => 'বকেয়া বিল প্রতিবেদন দর্শন',
                'description_en' => 'Access outstanding vendor bills, payment allocations, overdue obligations, and settlement histories',
                'description_bn' => 'সরবরাহকারীদের অপরিশোধিত ও বকেয়া বিল, পরিশোধ সমন্বয় ও মেয়াদোত্তীর্ণ দায়ের প্রতিবেদন দেখা',
            ],
            [
                'slug' => 'export_procurement_reports',
                'module' => 'procurement',
                'name_en' => 'Export Procurement Reports',
                'name_bn' => 'ক্রয় প্রতিবেদন এক্সপোর্ট',
                'description_en' => 'Export procurement report data to Excel/CSV and printable formats',
                'description_bn' => 'ক্রয় সংক্রান্ত প্রতিবেদনসমূহ এক্সেল/সিএসভি ও প্রিন্ট ফরম্যাটে ডাউনলোড বা মুদ্রণ করা',
            ],
        ];

        $createdPermissions = [];
        foreach ($permissions as $permData) {
            $createdPermissions[$permData['slug']] = Permission::updateOrCreate(
                ['slug' => $permData['slug']],
                $permData
            );
        }

        // Attach all to super_admin, temple_admin, and treasurer
        $fullAccessRoles = ['super_admin', 'temple_admin', 'treasurer'];
        foreach ($fullAccessRoles as $roleSlug) {
            $role = Role::where('slug', $roleSlug)->first();
            if ($role) {
                foreach ($createdPermissions as $perm) {
                    if (! $role->permissions()->where('permissions.id', $perm->id)->exists()) {
                        $role->permissions()->attach($perm->id);
                    }
                }
            }
        }

        // Attach view permissions (read-only) to committee_member
        $committeeRole = Role::where('slug', 'committee_member')->first();
        if ($committeeRole) {
            $viewSlugs = [
                'view_procurement_reports',
                'view_purchase_requisition_reports',
                'view_purchase_order_reports',
                'view_outstanding_vendor_bill_reports',
            ];
            foreach ($viewSlugs as $slug) {
                if (isset($createdPermissions[$slug])) {
                    $perm = $createdPermissions[$slug];
                    if (! $committeeRole->permissions()->where('permissions.id', $perm->id)->exists()) {
                        $committeeRole->permissions()->attach($perm->id);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $slugs = [
            'view_procurement_reports',
            'view_purchase_requisition_reports',
            'view_purchase_order_reports',
            'view_outstanding_vendor_bill_reports',
            'export_procurement_reports',
        ];

        Permission::whereIn('slug', $slugs)->delete();
    }
};
